<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Models\Services\WarService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceRoleRepository;

/**
 * Handles all HTTP requests for the Alliance War page.
 * * Refactored for Strict Dependency Injection.
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
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $levelCalculator, $statsRepo);
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

        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/war');
            return;
        }
        
        $targetAllianceId = (int)($_POST['target_alliance_id'] ?? 0);
        $name = (string)($_POST['war_name'] ?? 'War');
        $casusBelli = (string)($_POST['casus_belli'] ?? '');
        $goalKey = (string)($_POST['goal_key'] ?? 'credits_plundered');
        $goalThreshold = (int)($_POST['goal_threshold'] ?? 10000000);

        $this->warService->declareWar(
            $viewerData['user']->id,
            $targetAllianceId,
            $name,
            $casusBelli,
            $goalKey,
            $goalThreshold
        );
        
        $this->redirect('/alliance/war');
    }
}