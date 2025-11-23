<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\AllianceManagementService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles membership management (Kick, Leave, Roles).
 * * Refactored for Strict Dependency Injection & Centralized Validation.
 */
class AllianceMemberController extends BaseController
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
     * Handles a user's request to LEAVE their current alliance.
     */
    public function handleLeave(): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/dashboard');
            return;
        }

        $userId = $this->session->get('user_id');
        
        if ($this->mgmtService->leaveAlliance($userId)) {
            $this->redirect('/alliance/list');
        } else {
            // Failed (e.g. leader trying to leave without disbanding)
            $this->redirect('/dashboard');
        }
    }

    /**
     * Handles kicking a member from the alliance.
     */
    public function handleKickMember(array $vars): void
    {
        // 1. Validate Input (CSRF only, ID in route)
        $data = $this->validate($_POST, [
            'csrf_token' => 'required'
        ]);

        $allianceId = $this->session->get('alliance_id');
        
        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }

        $adminId = $this->session->get('user_id');
        $targetUserId = (int)($vars['id'] ?? 0);

        $this->mgmtService->kickMember($adminId, $targetUserId);
        
        $this->redirect('/alliance/profile/' . $allianceId);
    }

    /**
     * Handles assigning a new role to a member.
     */
    public function handleAssignRole(): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'target_user_id' => 'required|int',
            'role_id' => 'required|int'
        ]);

        $allianceId = $this->session->get('alliance_id');
        
        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }

        $adminId = $this->session->get('user_id');
        $this->mgmtService->changeMemberRole($adminId, $data['target_user_id'], $data['role_id']);
        
        $this->redirect('/alliance/profile/' . $allianceId);
    }
}