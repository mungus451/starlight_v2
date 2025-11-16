<?php

namespace App\Controllers;

use App\Models\Services\AllianceManagementService;
use App\Models\Repositories\ApplicationRepository;
use App\Models\Repositories\UserRepository;
use App\Core\Database;

/**
 * Handles all "write" POST requests for alliance management.
 */
class AllianceManagementController extends BaseController
{
    private AllianceManagementService $mgmtService;

    public function __construct()
    {
        parent::__construct();
        $this->mgmtService = new AllianceManagementService();
    }

    /**
     * Handles a user's request to APPLY to an alliance.
     */
    public function handleApply(array $vars): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/list'); // Redirect to list on bad token
            return;
        }

        $userId = $this->session->get('user_id');
        $allianceId = (int)($vars['id'] ?? 0);

        $this->mgmtService->applyToAlliance($userId, $allianceId);
        
        $this->redirect('/alliance/profile/' . $allianceId);
    }

    /**
     * Handles a user's request to CANCEL their own application.
     */
    public function handleCancelApp(array $vars): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/list');
            return;
        }

        $userId = $this->session->get('user_id');
        $appId = (int)($vars['id'] ?? 0);

        // We need the alliance ID to redirect back
        $app = (new ApplicationRepository(Database::getInstance()))->findById($appId);
        $allianceId = $app ? $app->alliance_id : 'list';

        $this->mgmtService->cancelApplication($userId, $appId);
        
        $this->redirect('/alliance/profile/' . $allianceId);
    }

    /**
     * Handles a user's request to LEAVE their current alliance.
     */
    public function handleLeave(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/dashboard');
            return;
        }

        $userId = $this->session->get('user_id');
        $user = (new UserRepository(Database::getInstance()))->findById($userId);
        
        $allianceId = $user->alliance_id ?? 'list';

        $this->mgmtService->leaveAlliance($userId);
        
        $this->redirect('/alliance/profile/' . $allianceId);
    }

    /**
     * Handles a leader's request to ACCEPT an application.
     */
    public function handleAcceptApp(array $vars): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/list');
            return;
        }

        $adminId = $this->session->get('user_id');
        $appId = (int)($vars['id'] ?? 0);
        
        $app = (new ApplicationRepository(Database::getInstance()))->findById($appId);
        $allianceId = $app ? $app->alliance_id : 'list';

        $this->mgmtService->acceptApplication($adminId, $appId);
        
        $this->redirect('/alliance/profile/' . $allianceId);
    }

    /**
     * Handles a leader's request to REJECT an application.
     */
    public function handleRejectApp(array $vars): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/list');
            return;
        }

        $adminId = $this->session->get('user_id');
        $appId = (int)($vars['id'] ?? 0);
        
        $app = (new ApplicationRepository(Database::getInstance()))->findById($appId);
        $allianceId = $app ? $app->alliance_id : 'list';

        $this->mgmtService->rejectApplication($adminId, $appId);
        
        $this->redirect('/alliance/profile/' . $allianceId);
    }
    
    /**
     * Handles a member's request to INVITE a user from their profile page.
     */
    public function handleInvite(array $vars): void
    {
        $token = $_POST['csrf_token'] ?? '';
        $inviterId = $this->session->get('user_id');
        $targetUserId = (int)($vars['id'] ?? 0);
        
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/profile/' . $targetUserId);
            return;
        }
        
        // The service handles all logic and flash messages
        $this->mgmtService->inviteUser($inviterId, $targetUserId);
        
        // Redirect back to the profile page
        $this->redirect('/profile/' . $targetUserId);
    }

    /**
     * Handles a member's request to DONATE to their alliance bank.
     */
    public function handleDonation(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        $donatorUserId = $this->session->get('user_id');
        
        // Get user's alliance ID for redirect
        $user = (new UserRepository(Database::getInstance()))->findById($donatorUserId);
        $allianceId = $user->alliance_id ?? 'list';

        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }
        
        $amount = (int)($_POST['amount'] ?? 0);

        // The service handles all logic and flash messages
        $this->mgmtService->donateToAlliance($donatorUserId, $amount);
        
        // Redirect back to the alliance profile page
        $this->redirect('/alliance/profile/' . $allianceId);
    }

    // --- METHODS FOR PHASE 13 ---

    /**
     * Handles updating the alliance's public profile (desc, image).
     */
    public function handleUpdateProfile(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        $adminId = $this->session->get('user_id');
        $user = (new UserRepository(Database::getInstance()))->findById($adminId);
        $allianceId = $user->alliance_id ?? 0;

        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }

        $description = (string)($_POST['description'] ?? '');
        $pfpUrl = (string)($_POST['profile_picture_url'] ?? '');

        $this->mgmtService->updateProfile($adminId, $allianceId, $description, $pfpUrl);
        
        $this->redirect('/alliance/profile/' . $allianceId);
    }

    /**
     * Handles kicking a member from the alliance.
     */
    public function handleKickMember(array $vars): void
    {
        $token = $_POST['csrf_token'] ?? '';
        $adminId = $this->session->get('user_id');
        $targetUserId = (int)($vars['id'] ?? 0);
        $user = (new UserRepository(Database::getInstance()))->findById($adminId);
        $allianceId = $user->alliance_id ?? 'list';
        
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }

        $this->mgmtService->kickMember($adminId, $targetUserId);
        
        $this->redirect('/alliance/profile/' . $allianceId);
    }

    /**
     * Handles assigning a new role to a member.
     */
    public function handleAssignRole(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        $adminId = $this->session->get('user_id');
        $user = (new UserRepository(Database::getInstance()))->findById($adminId);
        $allianceId = $user->alliance_id ?? 'list';
        
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }

        $targetUserId = (int)($_POST['target_user_id'] ?? 0);
        $newRoleId = (int)($_POST['role_id'] ?? 0);

        $this->mgmtService->changeMemberRole($adminId, $targetUserId, $newRoleId);
        
        $this->redirect('/alliance/profile/' . $allianceId);
    }
    
    // --- NEW METHODS FOR PHASE 5 (LOANS) ---

    /**
     * Handles a user's request for a loan.
     */
    public function handleLoanRequest(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        $userId = $this->session->get('user_id');
        $user = (new UserRepository(Database::getInstance()))->findById($userId);
        $allianceId = $user->alliance_id ?? 'list';
        
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }
        
        $amount = (int)($_POST['amount'] ?? 0);
        $this->mgmtService->requestLoan($userId, $amount);
        $this->redirect('/alliance/profile/' . $allianceId);
    }

    /**
     * Handles an admin's approval of a loan.
     */
    public function handleLoanApprove(array $vars): void
    {
        $token = $_POST['csrf_token'] ?? '';
        $adminId = $this->session->get('user_id');
        $user = (new UserRepository(Database::getInstance()))->findById($adminId);
        $allianceId = $user->alliance_id ?? 'list';
        
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }
        
        $loanId = (int)($vars['id'] ?? 0);
        $this->mgmtService->approveLoan($adminId, $loanId);
        $this->redirect('/alliance/profile/' . $allianceId);
    }

    /**
     * Handles an admin's denial of a loan.
     */
    public function handleLoanDeny(array $vars): void
    {
        $token = $_POST['csrf_token'] ?? '';
        $adminId = $this->session->get('user_id');
        $user = (new UserRepository(Database::getInstance()))->findById($adminId);
        $allianceId = $user->alliance_id ?? 'list';
        
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }
        
        $loanId = (int)($vars['id'] ?? 0);
        $this->mgmtService->denyLoan($adminId, $loanId);
        $this->redirect('/alliance/profile/' . $allianceId);
    }

    /**
     * Handles a user's repayment of a loan.
     */
    public function handleLoanRepay(array $vars): void
    {
        $token = $_POST['csrf_token'] ?? '';
        $userId = $this->session->get('user_id');
        $user = (new UserRepository(Database::getInstance()))->findById($userId);
        $allianceId = $user->alliance_id ?? 'list';
        
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }
        
        $loanId = (int)($vars['id'] ?? 0);
        $amount = (int)($_POST['amount'] ?? 0);
        $this->mgmtService->repayLoan($userId, $loanId, $amount);
        $this->redirect('/alliance/profile/' . $allianceId);
    }
}