<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Models\Services\AllianceManagementService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles alliance recruitment (applications and invites).
 */
class AllianceApplicationController extends BaseController
{
    private AllianceManagementService $mgmtService;

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

        $this->mgmtService->cancelApplication($userId, $appId);
        
        $this->redirect('/alliance/list');
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
}