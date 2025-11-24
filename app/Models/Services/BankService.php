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
 * * Refactored for Strict Dependency Injection.
 * * Decoupled from Session: Returns ServiceResponse.
 */
class BankService
{
    private PDO $db;
    private Config $config;
    
    private ResourceRepository $resourceRepo;
    private UserRepository $userRepo;
    private StatsRepository $statsRepo;

    /**
     * DI Constructor.
     *
     * @param PDO $db
     * @param Config $config
     * @param ResourceRepository $resourceRepo
     * @param UserRepository $userRepo
     * @param StatsRepository $statsRepo
     */
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
     *
     * @param int $userId
     * @return array
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
            $hoursPassed = ($now->getTimestamp() - $lastDepositTime->getTimestamp()) / 3600;
            
            $regenHours = $bankConfig['deposit_charge_regen_hours'];
            $chargesToRegen = floor($hoursPassed / $regenHours);

            if ($chargesToRegen > 0) {
                // Calculate how many charges we can *actually* add
                $chargesToAdd = min($chargesToRegen, $maxCharges - $currentCharges);
                
                if ($chargesToAdd > 0) {
                    $this->statsRepo->regenerateDepositCharges($userId, (int)$chargesToAdd);
                    // Re-fetch stats to show the user the updated value
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
     *
     * @param int $userId
     * @param int $amount
     * @return ServiceResponse
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
            return ServiceResponse::error('You can only deposit up to 80% (' . number_format($depositLimit) . ') of your on-hand credits at a time.');
        }
        if ($amount > 0 && $depositLimit <= 0 && $resources->credits > 0) {
             return ServiceResponse::error('Amount is too small to meet the 80% deposit rule.');
        }

        // 2. Check Deposit Charges
        if ($stats->deposit_charges <= 0) {
            return ServiceResponse::error('You have no deposit charges left. One regenerates every ' . $bankConfig['deposit_charge_regen_hours'] . ' hours.');
        }
        
        // 3. Check balance
        if ($resources->credits < $amount) {
            return ServiceResponse::error('You do not have enough credits on hand to deposit.');
        }

        $newCredits = $resources->credits - $amount;
        $newBanked = $resources->banked_credits + $amount;
        
        $this->db->beginTransaction();
        try {
            // 1. Update resources
            $this->resourceRepo->updateBankingCredits($userId, $newCredits, $newBanked);
            
            // 2. Update stats (charges)
            $this->statsRepo->updateDepositCharges($userId, $stats->deposit_charges - 1);
            
            $this->db->commit();
            
            return ServiceResponse::success('You successfully deposited ' . number_format($amount) . ' credits.');

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Bank Deposit Error: " . $e->getMessage());
            return ServiceResponse::error('A database error occurred. Please try again.');
        }
    }

    /**
     * Handles withdrawing credits from the bank to hand.
     *
     * @param int $userId
     * @param int $amount
     * @return ServiceResponse
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

        if ($this->resourceRepo->updateBankingCredits($userId, $newCredits, $newBanked)) {
            return ServiceResponse::success('You successfully withdrew ' . number_format($amount) . ' credits.');
        } else {
            return ServiceResponse::error('A database error occurred. Please try again.');
        }
    }

    /**
     * Handles transferring credits from one user to another.
     *
     * @param int $senderId
     * @param string $recipientName
     * @param int $amount
     * @return ServiceResponse
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
        
        $this->db->beginTransaction();
        
        try {
            $senderResources = $this->resourceRepo->findByUserId($senderId);
            $recipientResources = $this->resourceRepo->findByUserId($recipient->id);

            if (!$senderResources || !$recipientResources) {
                throw new \Exception('Resource records not found.');
            }

            if ($senderResources->credits < $amount) {
                $this->db->rollBack();
                return ServiceResponse::error('You do not have enough credits on hand to transfer.');
            }

            $senderNewCredits = $senderResources->credits - $amount;
            $recipientNewCredits = $recipientResources->credits + $amount;
            
            $this->resourceRepo->updateCredits($senderId, $senderNewCredits);
            $this->resourceRepo->updateCredits($recipient->id, $recipientNewCredits);

            $this->db->commit();
            return ServiceResponse::success('You successfully transferred ' . number_format($amount) . " credits to {$recipientName}.");

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Transfer Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred during the transfer.');
        }
    }
}