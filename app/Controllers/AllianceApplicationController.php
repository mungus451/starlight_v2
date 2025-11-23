<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\AllianceManagementService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles alliance recruitment (applications and invites).
 * * Refactored for Strict Dependency Injection & Centralized Validation.
 */
class AllianceApplicationController extends BaseController
{
    private AllianceManagementService $mgmtService;

    /**
     * DI Constructor.
     *
     * @param AllianceManagementService $mgmtService
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        AllianceManagementService $mgmtService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $validator, $levelCalculator, $statsRepo);
        $this->mgmtService = $mgmtService;
    }

    /**
     * Handles a user's request to APPLY to an alliance.
     */
    public function handleApply(array $vars): void
    {
        // 1. Validate Input (CSRF only, ID is in route)
        $data = $this->validate($_POST, [
            'csrf_token' => 'required'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
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
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
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
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
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
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
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
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/profile/' . ($vars['id'] ?? 0));
            return;
        }

        $inviterId = $this->session->get('user_id');
        $targetUserId = (int)($vars['id'] ?? 0);
        
        $this->mgmtService->inviteUser($inviterId, $targetUserId);
        
        $this->redirect('/profile/' . $targetUserId);
    }
}