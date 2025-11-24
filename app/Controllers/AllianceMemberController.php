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
 * * Refactored to consume ServiceResponse objects.
 * * Handles Session state updates for leaving/kicking.
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
        
        // 3. Execute Service
        $response = $this->mgmtService->leaveAlliance($userId);

        // 4. Handle Response
        if ($response->isSuccess()) {
            // Critical: Update Session State
            $this->session->set('alliance_id', null);
            
            $this->session->setFlash('success', $response->message);
            $this->redirect('/alliance/list');
        } else {
            $this->session->setFlash('error', $response->message);
            // If failed (e.g., is leader), redirect to dashboard or profile
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

        // 3. Execute Service
        $response = $this->mgmtService->kickMember($adminId, $targetUserId);
        
        // 4. Handle Response
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
            
            // Edge case: If admin kicked themselves (shouldn't happen via logic, but safety first)
            if ($adminId === $targetUserId) {
                $this->session->set('alliance_id', null);
                $this->redirect('/alliance/list');
                return;
            }
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
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
        
        // 3. Execute Service
        $response = $this->mgmtService->changeMemberRole($adminId, $data['target_user_id'], $data['role_id']);
        
        // 4. Handle Response
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/alliance/profile/' . $allianceId);
    }
}