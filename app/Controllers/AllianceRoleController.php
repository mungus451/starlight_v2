<?php

namespace App\Controllers;

use App\Models\Services\AllianceManagementService;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Repositories\UserRepository;
use App\Core\Database;

/**
 * Handles all requests for creating, editing, and deleting alliance roles.
 */
class AllianceRoleController extends BaseController
{
    private AllianceManagementService $mgmtService;
    private AllianceRoleRepository $roleRepo;
    private UserRepository $userRepo;

    public function __construct()
    {
        parent::__construct();
        // This controller uses the Management Service for "write" actions
        $this->mgmtService = new AllianceManagementService();
        
        // It uses the repositories directly for "read" actions
        $db = Database::getInstance();
        $this->roleRepo = new AllianceRoleRepository($db);
        $this->userRepo = new UserRepository($db);
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

        // --- THIS IS THE CHANGE ---
        $data = [
            'title' => 'Manage Alliance Roles',
            'roles' => $roles,
            'alliance_id' => $allianceId,
            'layoutMode' => 'full' // Use the full-width layout
        ];

        $this->render('alliance/manage_roles.php', $data);
    }

    /**
     * Handles creating a new role.
     */
    public function handleCreate(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        $adminId = $this->session->get('user_id');
        $admin = $this->userRepo->findById($adminId);
        $allianceId = $admin->alliance_id ?? 0;

        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/roles');
            return;
        }

        $name = (string)($_POST['role_name'] ?? '');
        $permissions = (array)($_POST['permissions'] ?? []);

        $this->mgmtService->createRole($adminId, $allianceId, $name, $permissions);
        
        $this->redirect('/alliance/roles');
    }

    /**
     * Handles updating an existing role.
     */
    public function handleUpdate(array $vars): void
    {
        $token = $_POST['csrf_token'] ?? '';
        $adminId = $this->session->get('user_id');
        $roleId = (int)($vars['id'] ?? 0);
        
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/roles');
            return;
        }

        $name = (string)($_POST['role_name'] ?? '');
        $permissions = (array)($_POST['permissions'] ?? []);
        
        $this->mgmtService->updateRole($adminId, $roleId, $name, $permissions);

        $this.redirect('/alliance/roles');
    }

    /**
     * Handles deleting a role.
     */
    public function handleDelete(array $vars): void
    {
        $token = $_POST['csrf_token'] ?? '';
        $adminId = $this->session->get('user_id');
        $roleId = (int)($vars['id'] ?? 0);
        
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/roles');
            return;
        }

        $this->mgmtService->deleteRole($adminId, $roleId);
        
        $this->redirect('/alliance/roles');
    }
}