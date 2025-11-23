<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\AllianceStructureService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRoleRepository;

/**
 * Handles all HTTP requests for the Alliance Structures page.
 * * Refactored for Strict Dependency Injection & Centralized Validation.
 */
class AllianceStructureController extends BaseController
{
    private AllianceStructureService $structureService;
    private UserRepository $userRepo;
    private AllianceRoleRepository $roleRepo;

    /**
     * DI Constructor.
     *
     * @param AllianceStructureService $structureService
     * @param UserRepository $userRepo
     * @param AllianceRoleRepository $roleRepo
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        AllianceStructureService $structureService,
        UserRepository $userRepo,
        AllianceRoleRepository $roleRepo,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $validator, $levelCalculator, $statsRepo);
        $this->structureService = $structureService;
        $this->userRepo = $userRepo;
        $this->roleRepo = $roleRepo;
    }

    /**
     * Displays the main alliance structures page.
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        $user = $this->userRepo->findById($userId);
        
        if ($user === null || $user->alliance_id === null) {
            $this->session->setFlash('error', 'You must be in an alliance to view this page.');
            $this->redirect('/alliance/list');
            return;
        }
        
        $allianceId = $user->alliance_id;
        $role = $this->roleRepo->findById($user->alliance_role_id);
        
        $data = $this->structureService->getStructureData($allianceId);
        
        $data['canManage'] = ($role && $role->can_manage_structures);
        $data['layoutMode'] = 'full';

        $this->render('alliance/structures.php', $data + ['title' => 'Alliance Structures']);
    }

    /**
     * Handles the structure upgrade form submission.
     */
    public function handleUpgrade(): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'structure_key' => 'required|string'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/structures');
            return;
        }
        
        $userId = $this->session->get('user_id');
        $this->structureService->purchaseOrUpgradeStructure($userId, $data['structure_key']);
        
        $this->redirect('/alliance/structures');
    }
}