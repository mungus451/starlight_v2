<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\AllianceManagementService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Repositories\UserRepository;

/**
 * Handles all requests for creating, editing, and deleting alliance roles.
 * * Refactored for Strict Dependency Injection & Centralized Validation.
 */
class AllianceRoleController extends BaseController
{
    private AllianceManagementService $mgmtService;
    private AllianceRoleRepository $roleRepo;
    private UserRepository $userRepo;

    /**
     * DI Constructor.
     *
     * @param AllianceManagementService $mgmtService
     * @param AllianceRoleRepository $roleRepo
     * @param UserRepository $userRepo
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        AllianceManagementService $mgmtService,
        AllianceRoleRepository $roleRepo,
        UserRepository $userRepo,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $validator, $levelCalculator, $statsRepo);
        $this->mgmtService = $mgmtService;
        $this->roleRepo = $roleRepo;
        $this->userRepo = $userRepo;
    }

    /**
     * Displays the main "Manage Roles" page.
     */
    public function showAll(): void
    {
        $adminId = $this->session->get('user_id');
        $admin = $this->userRepo->findById($adminId);
        $allianceId = $admin->alliance_id ?? 0;

        // Permission check: Must have 'can_manage_roles'
        $adminRole = $this->roleRepo->findById($admin->alliance_role_id);
        if (!$adminRole || !$adminRole->can_manage_roles) {
            $this->session->setFlash('error', 'You do not have permission to manage roles.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }

        $roles = $this->roleRepo->findByAllianceId($allianceId);

        $data = [
            'title' => 'Manage Alliance Roles',
            'roles' => $roles,
            'alliance_id' => $allianceId,
            'layoutMode' => 'full'
        ];

        $this->render('alliance/manage_roles.php', $data);
    }

    /**
     * Handles creating a new role.
     */
    public function handleCreate(): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'role_name' => 'required|string|min:3|max:30'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/roles');
            return;
        }

        $adminId = $this->session->get('user_id');
        $admin = $this->userRepo->findById($adminId);
        $allianceId = $admin->alliance_id ?? 0;

        // 3. Execute Logic
        // Permissions are an array of checkboxes, handled safely by the Service.
        $permissions = (array)($_POST['permissions'] ?? []);

        $this->mgmtService->createRole($adminId, $allianceId, $data['role_name'], $permissions);
        
        $this->redirect('/alliance/roles');
    }

    /**
     * Handles updating an existing role.
     */
    public function handleUpdate(array $vars): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'role_name' => 'required|string|min:3|max:30'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/roles');
            return;
        }

        $adminId = $this->session->get('user_id');
        $roleId = (int)($vars['id'] ?? 0);
        $permissions = (array)($_POST['permissions'] ?? []);
        
        $this->mgmtService->updateRole($adminId, $roleId, $data['role_name'], $permissions);

        $this->redirect('/alliance/roles');
    }

    /**
     * Handles deleting a role.
     */
    public function handleDelete(array $vars): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/roles');
            return;
        }

        $adminId = $this->session->get('user_id');
        $roleId = (int)($vars['id'] ?? 0);

        $this->mgmtService->deleteRole($adminId, $roleId);
        
        $this->redirect('/alliance/roles');
    }
}