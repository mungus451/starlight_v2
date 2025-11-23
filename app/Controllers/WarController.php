<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\WarService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceRoleRepository;

/**
 * Handles all HTTP requests for the Alliance War page.
 * * Refactored for Strict Dependency Injection & Centralized Validation.
 */
class WarController extends BaseController
{
    private WarService $warService;
    private UserRepository $userRepo;
    private AllianceRepository $allianceRepo;
    private AllianceRoleRepository $roleRepo;

    /**
     * DI Constructor.
     *
     * @param WarService $warService
     * @param UserRepository $userRepo
     * @param AllianceRepository $allianceRepo
     * @param AllianceRoleRepository $roleRepo
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        WarService $warService,
        UserRepository $userRepo,
        AllianceRepository $allianceRepo,
        AllianceRoleRepository $roleRepo,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $validator, $levelCalculator, $statsRepo);
        $this->warService = $warService;
        $this->userRepo = $userRepo;
        $this->allianceRepo = $allianceRepo;
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
            'canDeclareWar' => ($role && $role->can_declare_war)
        ];
    }

    /**
     * Displays the main war page (active wars, history, declare).
     */
    public function show(): void
    {
        $viewerData = $this->getViewerData();
        if ($viewerData === null) return;
        
        // Fetch alliances for the "Declare War" dropdown
        $allAlliances = $this->allianceRepo->getAllAlliances();
        $otherAlliances = array_filter($allAlliances, function($alliance) use ($viewerData) {
            return $alliance->id !== $viewerData['allianceId'];
        });

        $this->render('alliance/war.php', [
            'title' => 'Alliance War Room',
            'layoutMode' => 'full',
            'viewer' => $viewerData['user'],
            'canDeclareWar' => $viewerData['canDeclareWar'],
            'allianceId' => $viewerData['allianceId'],
            'otherAlliances' => $otherAlliances,
            'activeWars' => [], // Placeholder until WarService implements getWarData
            'historicalWars' => [] // Placeholder
        ]);
    }

    /**
     * Handles declaring a war.
     */
    public function handleDeclareWar(): void
    {
        $viewerData = $this->getViewerData();
        if ($viewerData === null) return;

        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'target_alliance_id' => 'required|int',
            'war_name' => 'required|string|min:3|max:100',
            'casus_belli' => 'nullable|string|max:1000',
            'goal_key' => 'required|string|in:credits_plundered,units_killed',
            'goal_threshold' => 'required|int|min:1'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/war');
            return;
        }
        
        // 3. Execute Logic
        $this->warService->declareWar(
            $viewerData['user']->id,
            $data['target_alliance_id'],
            $data['war_name'],
            $data['casus_belli'] ?? '',
            $data['goal_key'],
            $data['goal_threshold']
        );
        
        $this->redirect('/alliance/war');
    }
}