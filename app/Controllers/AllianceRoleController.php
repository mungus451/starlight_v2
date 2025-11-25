<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\AllianceManagementService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles all requests for creating, editing, and deleting alliance roles.
 * * Refactored Phase 1.4: Removed direct Repository dependencies.
 * * Now consumes ServiceResponse objects exclusively.
 */
class AllianceRoleController extends BaseController
{
    private AllianceManagementService $mgmtService;

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
     * Displays the main "Manage Roles" page.
     */
    public function showAll(): void
    {
        $userId = $this->session->get('user_id');
        
        // Service handles validation, permissions, and data fetching
        $response = $this->mgmtService->getRoleManagementData($userId);

        if (!$response->isSuccess()) {
            $this->session->setFlash('error', $response->message);
            // Redirect back to list if unauthorized
            $this->redirect('/alliance/list');
            return;
        }

        $data = $response->data;
        $data['layoutMode'] = 'full';
        $data['title'] = 'Manage Alliance Roles';

        $this->render('alliance/manage_roles.php', $data);
    }

    /**
     * Handles creating a new role.
     */
    public function handleCreate(): void
    {
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'role_name' => 'required|string|min:3|max:30'
        ]);

        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/roles');
            return;
        }

        $userId = $this->session->get('user_id');
        $allianceId = $this->session->get('alliance_id');
        
        if (!$allianceId) {
             $this->session->setFlash('error', 'You must be in an alliance.');
             $this->redirect('/alliance/list');
             return;
        }

        $permissions = (array)($_POST['permissions'] ?? []);
        
        $response = $this->mgmtService->createRole($userId, $allianceId, $data['role_name'], $permissions);
        
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/alliance/roles');
    }

    /**
     * Handles updating an existing role.
     */
    public function handleUpdate(array $vars): void
    {
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'role_name' => 'required|string|min:3|max:30'
        ]);

        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/roles');
            return;
        }

        $userId = $this->session->get('user_id');
        $roleId = (int)($vars['id'] ?? 0);
        $permissions = (array)($_POST['permissions'] ?? []);
        
        $response = $this->mgmtService->updateRole($userId, $roleId, $data['role_name'], $permissions);

        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }

        $this->redirect('/alliance/roles');
    }

    /**
     * Handles deleting a role.
     */
    public function handleDelete(array $vars): void
    {
        $data = $this->validate($_POST, [
            'csrf_token' => 'required'
        ]);

        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/roles');
            return;
        }

        $userId = $this->session->get('user_id');
        $roleId = (int)($vars['id'] ?? 0);

        $response = $this->mgmtService->deleteRole($userId, $roleId);
        
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/alliance/roles');
    }
}