<?php

namespace App\Controllers;

use App\Models\Services\DiplomacyService;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Core\Database;

/**
 * Handles all HTTP requests for the Alliance Diplomacy page.
 */
class DiplomacyController extends BaseController
{
    private DiplomacyService $diplomacyService;
    private UserRepository $userRepo;
    private AllianceRoleRepository $roleRepo;

    public function __construct()
    {
        parent::__construct();
        $this->diplomacyService = new DiplomacyService();
        
        $db = Database::getInstance();
        $this->userRepo = new UserRepository($db);
        $this->roleRepo = new AllianceRoleRepository($db);
    }

    /**
     * Helper to get common data and check permissions.
     */
    private function getViewerData(): ?array
    {
        $userId = $this->session->get('user_id');
        $user = $this->userRepo->findById($userId);
        
        if ($user === null || $user->alliance_id === null) {
            $this->session->setFlash('error', 'You must be in an alliance to view this page.');
            $this->redirect('/alliance/list');
            return null;
        }
        
        $role = $this->roleRepo->findById($user->alliance_role_id);
        
        return [
            'user' => $user,
            'role' => $role,
            'allianceId' => $user->alliance_id,
            'canManage' => ($role && $role->can_manage_diplomacy)
        ];
    }

    /**
     * Displays the main diplomacy page.
     */
    public function show(): void
    {
        $viewerData = $this->getViewerData();
        if ($viewerData === null) return;
        
        $data = $this->diplomacyService->getDiplomacyData($viewerData['allianceId']);

        $this->render('alliance/diplomacy.php', $data + [
            'title' => 'Alliance Diplomacy',
            'layoutMode' => 'full',
            'viewer' => $viewerData['user'],
            'canManage' => $viewerData['canManage'],
            'allianceId' => $viewerData['allianceId']
        ]);
    }

    /**
     * Handles proposing a new treaty.
     */
    public function handleProposeTreaty(): void
    {
        $viewerData = $this->getViewerData();
        if ($viewerData === null) return;

        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/diplomacy');
            return;
        }
        
        $targetAllianceId = (int)($_POST['target_alliance_id'] ?? 0);
        $treatyType = (string)($_POST['treaty_type'] ?? '');
        $terms = (string)($_POST['terms'] ?? '');

        $this->diplomacyService->proposeTreaty($viewerData['user']->id, $targetAllianceId, $treatyType, $terms);
        
        $this->redirect('/alliance/diplomacy');
    }

    /**
     * Handles accepting a treaty.
     */
    public function handleAcceptTreaty(array $vars): void
    {
        $viewerData = $this->getViewerData();
        if ($viewerData === null) return;

        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/diplomacy');
            return;
        }

        $treatyId = (int)($vars['id'] ?? 0);
        $this->diplomacyService->acceptTreaty($viewerData['user']->id, $treatyId);
        $this->redirect('/alliance/diplomacy');
    }

    /**
     * Handles declining a treaty proposal.
     */
    public function handleDeclineTreaty(array $vars): void
    {
        $viewerData = $this->getViewerData();
        if ($viewerData === null) return;

        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/diplomacy');
            return;
        }

        $treatyId = (int)($vars['id'] ?? 0);
        $this->diplomacyService->endTreaty($viewerData['user']->id, $treatyId, 'decline');
        $this->redirect('/alliance/diplomacy');
    }

    /**
     * Handles breaking an active treaty.
     */
    public function handleBreakTreaty(array $vars): void
    {
        $viewerData = $this->getViewerData();
        if ($viewerData === null) return;

        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/diplomacy');
            return;
        }

        $treatyId = (int)($vars['id'] ?? 0);
        $this->diplomacyService->endTreaty($viewerData['user']->id, $treatyId, 'break');
        $this->redirect('/alliance/diplomacy');
    }

    /**
     * Handles declaring a rivalry.
     */
    public function handleDeclareRivalry(): void
    {
        $viewerData = $this->getViewerData();
        if ($viewerData === null) return;

        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/diplomacy');
            return;
        }

        $targetAllianceId = (int)($_POST['target_alliance_id'] ?? 0);
        $this->diplomacyService->declareRivalry($viewerData['user']->id, $targetAllianceId);
        $this->redirect('/alliance/diplomacy');
    }
}