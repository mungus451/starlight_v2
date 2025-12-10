<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\UserRepository;
use PDO;
use Throwable;
use DateTime;

/**
 * Handles all business logic for the Bank.
 * * Refactored: Implements Transaction Owner Pattern for atomic testing.
 */
class BankService
{
    private PDO $db;
    private Config $config;
    
    private ResourceRepository $resourceRepo;
    private UserRepository $userRepo;
    private StatsRepository $statsRepo;

    public function __construct(
        PDO $db,
        Config $config,
        ResourceRepository $resourceRepo,
        UserRepository $userRepo,
        StatsRepository $statsRepo
    ) {
        $this->db = $db;
        $this->config = $config;
        $this->resourceRepo = $resourceRepo;
        $this->userRepo = $userRepo;
        $this->statsRepo = $statsRepo;
    }

    /**
     * Gets the resource and stats data needed for the Bank view.
     * Handles deposit charge regeneration.
     */
    public function getBankData(int $userId): array
    {
        $stats = $this->statsRepo->findByUserId($userId);
        $bankConfig = $this->config->get('bank');
        
        // --- Calculate Charge Regeneration ---
        $currentCharges = $stats->deposit_charges;
        $maxCharges = $bankConfig['deposit_max_charges'];

        if ($currentCharges < $maxCharges && $stats->last_deposit_at !== null) {
            $lastDepositTime = new DateTime($stats->last_deposit_at);
            $now = new DateTime();
            
            // Calculate difference in seconds for precision, then convert to hours
            $diffSeconds = $now->getTimestamp() - $lastDepositTime->getTimestamp();
            $hoursPassed = $diffSeconds / 3600;
            
            $regenHours = $bankConfig['deposit_charge_regen_hours'];
            
            // Floor to get full cycles passed
            $chargesToRegen = (int)floor($hoursPassed / $regenHours);

            if ($chargesToRegen > 0) {
                $chargesToAdd = min($chargesToRegen, $maxCharges - $currentCharges);
                
                if ($chargesToAdd > 0) {
                    $this->statsRepo->regenerateDepositCharges($userId, $chargesToAdd);
                    $stats = $this->statsRepo->findByUserId($userId);
                }
            }
        }

        return [
            'resources' => $this->resourceRepo->findByUserId($userId),
            'stats' => $stats,
            'bankConfig' => $bankConfig
        ];
    }

    /**
     * Handles depositing credits from hand into the bank.
     */
    public function deposit(int $userId, int $amount): ServiceResponse
    {
        if ($amount <= 0) {
            return ServiceResponse::error('Amount to deposit must be a positive number.');
        }

        $resources = $this->resourceRepo->findByUserId($userId);
        $stats = $this->statsRepo->findByUserId($userId);
        $bankConfig = $this->config->get('bank');

        // 1. Check 80% Limit
        $depositLimit = floor($resources->credits * $bankConfig['deposit_percent_limit']);
        if ($amount > $depositLimit && $depositLimit > 0) {
            return ServiceResponse::error('You can only deposit up to ' . ($bankConfig['deposit_percent_limit'] * 100) . '% (' . number_format($depositLimit) . ') of your credits.');
        }

        // 2. Check Deposit Charges
        if ($stats->deposit_charges <= 0) {
            return ServiceResponse::error('You have no deposit charges left.');
        }
        
        // 3. Check balance
        if ($resources->credits < $amount) {
            return ServiceResponse::error('You do not have enough credits on hand.');
        }

        $newCredits = $resources->credits - $amount;
        $newBanked = $resources->banked_credits + $amount;
        
        // Transaction Owner Pattern
        $transactionStartedByMe = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $transactionStartedByMe = true;
        }

        try {
            // 1. Update resources
            $this->resourceRepo->updateBankingCredits($userId, $newCredits, $newBanked);
            
            // 2. Update stats (deduct 1 charge, update timestamp)
            $this->statsRepo->updateDepositCharges($userId, $stats->deposit_charges - 1);
            
            if ($transactionStartedByMe) {
                $this->db->commit();
            }
            
            return ServiceResponse::success('You successfully deposited ' . number_format($amount) . ' credits.');

        } catch (Throwable $e) {
            if ($transactionStartedByMe && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Bank Deposit Error: " . $e->getMessage());
            return ServiceResponse::error('A database error occurred. Please try again.');
        }
    }

    /**
     * Handles withdrawing credits from the bank to hand.
     */
    public function withdraw(int $userId, int $amount): ServiceResponse
    {
        if ($amount <= 0) {
            return ServiceResponse::error('Amount to withdraw must be a positive number.');
        }

        $resources = $this->resourceRepo->findByUserId($userId);

        if ($resources->banked_credits < $amount) {
            return ServiceResponse::error('You do not have enough banked credits to withdraw.');
        }

        $newCredits = $resources->credits + $amount;
        $newBanked = $resources->banked_credits - $amount;

        // Simple atomic update, no complex multi-table transaction needed unless logging is added
        if ($this->resourceRepo->updateBankingCredits($userId, $newCredits, $newBanked)) {
            return ServiceResponse::success('You successfully withdrew ' . number_format($amount) . ' credits.');
        } else {
            return ServiceResponse::error('A database error occurred. Please try again.');
        }
    }

    /**
     * Handles transferring credits from one user to another.
     */
    public function transfer(int $senderId, string $recipientName, int $amount): ServiceResponse
    {
        if ($amount <= 0) {
            return ServiceResponse::error('Amount to transfer must be a positive number.');
        }
        
        if (empty(trim($recipientName))) {
            return ServiceResponse::error('You must enter a recipient.');
        }

        $recipient = $this->userRepo->findByCharacterName($recipientName);

        if (!$recipient) {
            return ServiceResponse::error("Character '{$recipientName}' not found.");
        }

        if ($recipient->id === $senderId) {
            return ServiceResponse::error('You cannot transfer credits to yourself.');
        }
        
        // Transaction Owner Pattern
        $transactionStartedByMe = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $transactionStartedByMe = true;
        }
        
        try {
            // Re-fetch within transaction to lock/ensure fresh data (in a real app, use FOR UPDATE)
            $senderResources = $this->resourceRepo->findByUserId($senderId);
            $recipientResources = $this->resourceRepo->findByUserId($recipient->id);

            if ($senderResources->credits < $amount) {
                // We don't rollback here because we haven't written anything yet, just return error
                return ServiceResponse::error('You do not have enough credits on hand to transfer.');
            }

            $senderNewCredits = $senderResources->credits - $amount;
            $recipientNewCredits = $recipientResources->credits + $amount;
            
            $this->resourceRepo->updateCredits($senderId, $senderNewCredits);
            $this->resourceRepo->updateCredits($recipient->id, $recipientNewCredits);

            if ($transactionStartedByMe) {
                $this->db->commit();
            }
            return ServiceResponse::success('You successfully transferred ' . number_format($amount) . " credits to {$recipientName}.");

        } catch (Throwable $e) {
            if ($transactionStartedByMe && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Transfer Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred during the transfer.');
        }
    }
}