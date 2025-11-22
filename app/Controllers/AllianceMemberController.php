<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Models\Services\AllianceManagementService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles membership management (Kick, Leave, Roles).
 */
class AllianceMemberController extends BaseController
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
}