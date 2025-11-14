<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Session;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStats;
use PDO;
use Throwable;
use DateTime;

/**
 * Handles all business logic for the Bank.
 */
class BankService
{
    private PDO $db;
    private Session $session;
    private Config $config;
    private ResourceRepository $resourceRepo;
    private UserRepository $userRepo;
    private StatsRepository $statsRepo;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->session = new Session();
        $this->config = new Config(); // NEW
        
        // This service now needs three repositories
        $this->resourceRepo = new ResourceRepository($this->db);
        $this->userRepo = new UserRepository($this->db);
        $this->statsRepo = new StatsRepository($this->db); // NEW
    }

    /**
     * Gets the resource and stats data needed for the Bank view.
     * This method now also handles deposit charge regeneration.
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
                    $this->statsRepo->regenerateDepositCharges($userId, $chargesToAdd);
                    // Re-fetch stats to show the user the updated value
                    $stats = $this->statsRepo->findByUserId($userId);
                }
            }
        }
        // --- End Charge Regeneration ---

        return [
            'resources' => $this->resourceRepo->findByUserId($userId),
            'stats' => $stats,
            'bankConfig' => $bankConfig
        ];
    }

    /**
     * Handles depositing credits from hand into the bank.
     * Now includes new validation for 80% limit and deposit charges.
     *
     * @param int $userId
     * @param int $amount
     * @return bool True on success, false on failure
     */
    public function deposit(int $userId, int $amount): bool
    {
        if ($amount <= 0) {
            $this->session->setFlash('error', 'Amount to deposit must be a positive number.');
            return false;
        }

        $resources = $this->resourceRepo->findByUserId($userId);
        $stats = $this->statsRepo->findByUserId($userId);
        $bankConfig = $this->config->get('bank');

        // --- NEW VALIDATION ---
        
        // 1. Check 80% Limit
        $depositLimit = floor($resources->credits * $bankConfig['deposit_percent_limit']);
        if ($amount > $depositLimit && $depositLimit > 0) {
            $this->session->setFlash('error', 'You can only deposit up to 80% (' . number_format($depositLimit) . ') of your on-hand credits at a time.');
            return false;
        }
        // Handle case where user has lots of credits but 80% is 0 (rounding)
        if ($amount > 0 && $depositLimit <= 0 && $resources->credits > 0) {
             $this->session->setFlash('error', 'Amount is too small to meet the 80% deposit rule.');
            return false;
        }

        // 2. Check Deposit Charges (Regeneration is handled in getBankData)
        if ($stats->deposit_charges <= 0) {
            $this->session->setFlash('error', 'You have no deposit charges left. One regenerates every ' . $bankConfig['deposit_charge_regen_hours'] . ' hours.');
            return false;
        }
        
        // 3. Check if user has enough credits (existing check)
        if ($resources->credits < $amount) {
            $this->session->setFlash('error', 'You do not have enough credits on hand to deposit.');
            return false;
        }
        // --- END NEW VALIDATION ---

        $newCredits = $resources->credits - $amount;
        $newBanked = $resources->banked_credits + $amount;
        
        // --- NEW TRANSACTION: Must update two tables ---
        $this->db->beginTransaction();
        try {
            // 1. Update resources (credits)
            $this->resourceRepo->updateBankingCredits($userId, $newCredits, $newBanked);
            
            // 2. Update stats (charges)
            $this->statsRepo->updateDepositCharges($userId, $stats->deposit_charges - 1);
            
            $this->db->commit();
            
            $this->session->setFlash('success', 'You successfully deposited ' . number_format($amount) . ' credits.');
            return true;

        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log("Bank Deposit Error: " . $e->getMessage());
            $this->session->setFlash('error', 'A database error occurred. Please try again.');
            return false;
        }
    }

    /**
     * Handles withdrawing credits from the bank to hand.
     *
     * @param int $userId
     * @param int $amount
     * @return bool True on success, false on failure
     */
    public function withdraw(int $userId, int $amount): bool
    {
        if ($amount <= 0) {
            $this->session->setFlash('error', 'Amount to withdraw must be a positive number.');
            return false;
        }

        $resources = $this->resourceRepo->findByUserId($userId);

        if ($resources->banked_credits < $amount) {
            $this->session->setFlash('error', 'You do not have enough banked credits to withdraw.');
            return false;
        }

        $newCredits = $resources->credits + $amount;
        $newBanked = $resources->banked_credits - $amount;

        // This is a single-table operation, so we can use the simple repo method
        if ($this->resourceRepo->updateBankingCredits($userId, $newCredits, $newBanked)) {
            $this->session->setFlash('success', 'You successfully withdrew ' . number_format($amount) . ' credits.');
            return true;
        } else {
            $this->session->setFlash('error', 'A database error occurred. Please try again.');
            return false;
        }
    }

    /**
     * Handles transferring credits from one user to another.
     * This is wrapped in a transaction.
     *
     * @param int $senderId
     * @param string $recipientName
     * @param int $amount
     * @return bool True on success, false on failure
     */
    public function transfer(int $senderId, string $recipientName, int $amount): bool
    {
        if ($amount <= 0) {
            $this->session->setFlash('error', 'Amount to transfer must be a positive number.');
            return false;
        }
        
        if (empty(trim($recipientName))) {
            $this->session->setFlash('error', 'You must enter a recipient.');
            return false;
        }

        $recipient = $this->userRepo->findByCharacterName($recipientName);

        if (!$recipient) {
            $this->session->setFlash('error', "Character '{$recipientName}' not found.");
            return false;
        }

        if ($recipient->id === $senderId) {
            $this->session->setFlash('error', 'You cannot transfer credits to yourself.');
            return false;
        }
        
        // Start the transaction
        $this->db->beginTransaction();
        
        try {
            // Get resources for both parties
            $senderResources = $this->resourceRepo->findByUserId($senderId);
            $recipientResources = $this->resourceRepo->findByUserId($recipient->id);

            if (!$senderResources || !$recipientResources) {
                // This should never happen if users are created correctly
                throw new \Exception('Resource records not found.');
            }

            // Check if sender has enough credits ON HAND
            if ($senderResources->credits < $amount) {
                $this->session->setFlash('error', 'You do not have enough credits on hand to transfer.');
                $this->db->rollBack();
                return false;
            }

            // Calculate new totals
            $senderNewCredits = $senderResources->credits - $amount;
            $recipientNewCredits = $recipientResources->credits + $amount;
            
            // Update both users using the new updateCredits method
            $this->resourceRepo->updateCredits($senderId, $senderNewCredits);
            $this->resourceRepo->updateCredits($recipient->id, $recipientNewCredits);

            // If all queries were successful, commit
            $this->db->commit();
            $this->session->setFlash('success', 'You successfully transferred ' . number_format($amount) . " credits to {$recipientName}.");
            return true;

        } catch (Throwable $e) {
            // If any query fails, roll back
            $this->db->rollBack();
            error_log('Transfer Error: ' . $e->getMessage());
            $this->session->setFlash('error', 'A database error occurred during the transfer.');
            return false;
        }
    }
}