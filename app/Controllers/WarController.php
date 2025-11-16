<?php

namespace App\Controllers;

use App\Models\Services\WarService;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Core\Database;

/**
 * Handles all HTTP requests for the Alliance War page.
 */
class WarController extends BaseController
{
    private WarService $warService;
    private UserRepository $userRepo;
    private AllianceRepository $allianceRepo;
    private AllianceRoleRepository $roleRepo;

    public function __construct()
    {
        parent::__construct();
        $this->warService = new WarService();
        
        $db = Database::getInstance();
        $this->userRepo = new UserRepository($db);
        $this->allianceRepo = new AllianceRepository($db);
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
        
        // This service will be expanded to fetch war data
        // $data = $this->warService->getWarData($viewerData['allianceId']);
        
        // For now, just get data for the "Declare War" form
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
            'activeWars' => [], // Placeholder
            'historicalWars' => [] // Placeholder
        ]);
    }

    /**
     * Handles proposing a new treaty.
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