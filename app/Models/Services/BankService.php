<?php

namespace App\Models\Services;

use App\Core\Database;
use App\Core\Session;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Entities\UserResource;
use PDO;
use Throwable;

/**
 * Handles all business logic for the Bank.
 */
class BankService
{
    private PDO $db;
    private Session $session;
    private ResourceRepository $resourceRepo;
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->session = new Session();
        
        // This service needs two repositories:
        // ResourceRepository to move credits
        // UserRepository to find users for transfers
        $this->resourceRepo = new ResourceRepository($this->db);
        $this->userRepo = new UserRepository($this->db);
    }

    /**
     * Gets the resource data needed for the Bank view.
     *
     * @param int $userId
     * @return UserResource|null
     */
    public function getBankData(int $userId): ?UserResource
    {
        return $this->resourceRepo->findByUserId($userId);
    }

    /**
     * Handles depositing credits from hand into the bank.
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

        if ($resources->credits < $amount) {
            $this->session->setFlash('error', 'You do not have enough credits on hand to deposit.');
            return false;
        }

        $newCredits = $resources->credits - $amount;
        $newBanked = $resources->banked_credits + $amount;

        if ($this->resourceRepo->updateBankingCredits($userId, $newCredits, $newBanked)) {
            $this->session->setFlash('success', 'You successfully deposited ' . number_format($amount) . ' credits.');
            return true;
        } else {
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