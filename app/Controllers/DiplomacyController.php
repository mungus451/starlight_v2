<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\DiplomacyService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRoleRepository;

/**
 * Handles all HTTP requests for the Alliance Diplomacy page.
 * * Refactored for Strict Dependency Injection & Centralized Validation.
 */
class DiplomacyController extends BaseController
{
    private DiplomacyService $diplomacyService;
    private UserRepository $userRepo;
    private AllianceRoleRepository $roleRepo;

    /**
     * DI Constructor.
     *
     * @param DiplomacyService $diplomacyService
     * @param UserRepository $userRepo
     * @param AllianceRoleRepository $roleRepo
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        DiplomacyService $diplomacyService,
        UserRepository $userRepo,
        AllianceRoleRepository $roleRepo,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $validator, $levelCalculator, $statsRepo);
        $this->diplomacyService = $diplomacyService;
        $this->userRepo = $userRepo;
        $this->roleRepo = $roleRepo;
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

        $data['layoutMode'] = 'full';
        $data['viewer'] = $viewerData['user'];
        $data['canManage'] = $viewerData['canManage'];
        $data['allianceId'] = $viewerData['allianceId'];
        $data['title'] = 'Alliance Diplomacy';

        $this->render('alliance/diplomacy.php', $data);
    }

    /**
     * Handles proposing a new treaty.
     */
    public function handleProposeTreaty(): void
    {
        $viewerData = $this->getViewerData();
        if ($viewerData === null) return;

        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'target_alliance_id' => 'required|int',
            'treaty_type' => 'required|string|in:peace,non_aggression,mutual_defense',
            'terms' => 'nullable|string|max:5000'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/diplomacy');
            return;
        }

        // 3. Execute Logic
        $this->diplomacyService->proposeTreaty(
            $viewerData['user']->id, 
            $data['target_alliance_id'], 
            $data['treaty_type'], 
            $data['terms'] ?? ''
        );
        
        $this->redirect('/alliance/diplomacy');
    }

    /**
     * Handles accepting a treaty.
     */
    public function handleAcceptTreaty(array $vars): void
    {
        $viewerData = $this->getViewerData();
        if ($viewerData === null) return;

        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
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

        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
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

        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
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

        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'target_alliance_id' => 'required|int'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/diplomacy');
            return;
        }

        $this->diplomacyService->declareRivalry($viewerData['user']->id, $data['target_alliance_id']);
        $this->redirect('/alliance/diplomacy');
    }
}