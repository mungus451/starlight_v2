<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Models\Services\AllianceManagementService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\ApplicationRepository;
use App\Models\Repositories\UserRepository;
use App\Core\Database;

/**
 * Handles all "write" POST requests for alliance management.
 * * Refactored for Strict Dependency Injection.
 */
class AllianceManagementController extends BaseController
{
    private AllianceManagementService $mgmtService;

    /**
     * DI Constructor.
     *
     * @param AllianceManagementService $mgmtService
     * @param Session $session
     * @param CSRFService $csrfService
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        AllianceManagementService $mgmtService,
        Session $session,
        CSRFService $csrfService,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $levelCalculator, $statsRepo);
        $this->mgmtService = $mgmtService;
    }

    /**
     * Handles a user's request to APPLY to an alliance.
     */
    public function handleApply(array $vars): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/list');
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

        // Note: We need to find the alliance ID to redirect back.
        // Ideally, the service returns this or we redirect to a generic page.
        // For strict MVC, we shouldn't query the DB here.
        // However, to keep logic in service, we can just call service and redirect to list if successful.
        // Or we can fetch the app via a repo injected here (but we want to minimize controller deps).
        // Let's blindly redirect to list for now, or fetch via service if we added a getter.
        // Since we are refactoring, let's stick to the original behavior but use a repo if needed.
        // Wait, the controller shouldn't use `new ApplicationRepository`.
        // We will simplify: redirect to /alliance/list.
        
        $this->mgmtService->cancelApplication($userId, $appId);
        
        $this->redirect('/alliance/list');
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
        
        // We don't know the alliance ID to redirect to profile before leaving.
        // But if they leave, they can't see the profile anyway.
        // Redirect to dashboard or list.
        
        if ($this->mgmtService->leaveAlliance($userId)) {
            $this->redirect('/alliance/list');
        } else {
            // Failed (e.g. leader), redirect back to... where?
            // We don't know the ID easily without querying.
            $this->redirect('/dashboard');
        }
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
        
        $this->mgmtService->acceptApplication($adminId, $appId);
        
        // We redirect to the alliance profile. We need the alliance ID.
        // Since we don't have it easily, we redirect to the user's own alliance profile (since they are admin).
        $allianceId = $this->session->get('alliance_id');
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
        
        $this->mgmtService->rejectApplication($adminId, $appId);
        
        $allianceId = $this->session->get('alliance_id');
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
        
        $this->mgmtService->inviteUser($inviterId, $targetUserId);
        
        $this->redirect('/profile/' . $targetUserId);
    }

    /**
     * Handles a member's request to DONATE to their alliance bank.
     */
    public function handleDonation(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        $donatorUserId = $this->session->get('user_id');
        $allianceId = $this->session->get('alliance_id');

        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }
        
        $amount = (int)($_POST['amount'] ?? 0);

        $this->mgmtService->donateToAlliance($donatorUserId, $amount);
        
        $this->redirect('/alliance/profile/' . $allianceId);
    }

    /**
     * Handles updating the alliance's public profile (desc, image).
     */
    public function handleUpdateProfile(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        $adminId = $this->session->get('user_id');
        $allianceId = $this->session->get('alliance_id');

        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }

        $description = (string)($_POST['description'] ?? '');
        $pfpUrl = (string)($_POST['profile_picture_url'] ?? '');
        $isJoinable = (bool)($_POST['is_joinable'] ?? false);

        $this->mgmtService->updateProfile($adminId, $allianceId, $description, $pfpUrl, $isJoinable);
        
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
        $allianceId = $this->session->get('alliance_id');
        
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
        $allianceId = $this->session->get('alliance_id');
        
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
    
    // --- LOANS ---

    /**
     * Handles a user's request for a loan.
     */
    public function handleLoanRequest(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        $userId = $this->session->get('user_id');
        $allianceId = $this->session->get('alliance_id');
        
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
        $allianceId = $this->session->get('alliance_id');
        
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
        $allianceId = $this->session->get('alliance_id');
        
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
        $allianceId = $this->session->get('alliance_id');
        
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