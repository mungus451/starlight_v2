<?php

namespace App\Models\Services;

use App\Core\Database;
use App\Core\Session;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ApplicationRepository;
use PDO;
use Throwable;

/**
 * Handles all "write" logic for managing alliance membership.
 */
class AllianceManagementService
{
    private PDO $db;
    private Session $session;
    private AllianceRepository $allianceRepo;
    private UserRepository $userRepo;
    private ApplicationRepository $appRepo;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->session = new Session();
        
        $this->allianceRepo = new AllianceRepository($this->db);
        $this->userRepo = new UserRepository($this->db);
        $this->appRepo = new ApplicationRepository($this->db);
    }

    /**
     * A user applies to join an alliance.
     */
    public function applyToAlliance(int $userId, int $allianceId): bool
    {
        $user = $this->userRepo->findById($userId);
        if ($user->alliance_id !== null) {
            $this->session->setFlash('error', 'You are already in an alliance.');
            return false;
        }

        if ($this->appRepo->findByUserAndAlliance($userId, $allianceId)) {
            $this.session->setFlash('error', 'You have already applied to this alliance.');
            return false;
        }

        if ($this->appRepo->create($userId, $allianceId)) {
            $this->session->setFlash('success', 'Application sent!');
            return true;
        }

        $this->session->setFlash('error', 'A database error occurred.');
        return false;
    }

    /**
     * A user cancels their own application.
     */
    public function cancelApplication(int $userId, int $appId): bool
    {
        $app = $this->appRepo->findById($appId);

        if (!$app || $app->user_id !== $userId) {
            $this.session->setFlash('error', 'Invalid application.');
            return false;
        }

        $this->appRepo->delete($appId);
        $this->session->setFlash('success', 'Application cancelled.');
        return true;
    }

    /**
     * A user (not a leader) leaves their current alliance.
     */
    public function leaveAlliance(int $userId): bool
    {
        $user = $this->userRepo->findById($userId);

        if ($user->alliance_id === null) {
            $this->session->setFlash('error', 'You are not in an alliance.');
            return false;
        }

        if ($user->alliance_role === 'Leader') {
            $this.session->setFlash('error', 'Leaders must disband the alliance (feature coming soon). You cannot leave.');
            return false;
        }

        $this->userRepo->leaveAlliance($userId);
        $this->session->setFlash('success', 'You have left the alliance.');
        return true;
    }

    /**
     * An alliance leader accepts a pending application.
     */
    public function acceptApplication(int $adminId, int $appId): bool
    {
        $app = $this->appRepo->findById($appId);
        if (!$app) {
            $this.session->setFlash('error', 'Application not found.');
            return false;
        }

        // Check if admin has permission for this alliance
        if (!$this->checkPermission($adminId, $app->alliance_id, 'Leader')) {
            $this.session->setFlash('error', 'You do not have permission to do this.');
            return false;
        }

        $targetUser = $this->userRepo->findById($app->user_id);
        if ($targetUser->alliance_id !== null) {
            $this.session->setFlash('error', 'This user has already joined another alliance.');
            $this->appRepo->delete($appId); // Clean up the app
            return false;
        }

        // --- Transaction ---
        // --- FIX ---
        $this->db->beginTransaction();
        try {
            // 1. Set the user's alliance
            $this->userRepo->setAlliance($targetUser->id, $app->alliance_id, 'Member');

            // 2. Delete ALL applications for this user (they're in an alliance now)
            $this->appRepo->deleteByUser($targetUser->id);

            $this->db->commit();
        } catch (Throwable $e) {
            // --- FIX ---
            $this->db->rollBack();
            error_log('Accept Application Error: ' . $e->getMessage());
            // --- FIX ---
            $this.session->setFlash('error', 'A database error occurred.');
            return false;
        }
        
        $this->session->setFlash('success', 'Member accepted!');
        return true;
    }

    /**
     * An alliance leader rejects a pending application.
     */
    public function rejectApplication(int $adminId, int $appId): bool
    {
        $app = $this->appRepo->findById($appId);
        if (!$app) {
            $this.session->setFlash('error', 'Application not found.');
            return false;
        }

        // Check if admin has permission for this alliance
        // --- FIX ---
        if (!$this->checkPermission($adminId, $app->alliance_id, 'Leader')) {
            // --- FIX ---
            $this.session->setFlash('error', 'You do not have permission to do this.');
            return false;
        }

        $this.appRepo->delete($appId);
        $this->session->setFlash('success', 'Application rejected.');
        return true;
    }

    /**
     * Helper function to check if a user has a specific role in an alliance.
     */
    private function checkPermission(int $userId, int $allianceId, string $role = 'Leader'): bool
    {
        $user = $this->userRepo->findById($userId);
        
        return $user && $user->alliance_id === $allianceId && $user->alliance_role === $role;
    }
}