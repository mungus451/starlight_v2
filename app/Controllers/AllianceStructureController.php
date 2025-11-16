<?php

namespace App\Controllers;

use App\Models\Services\AllianceStructureService;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Core\Database;

/**
 * Handles all HTTP requests for the Alliance Structures page.
 */
class AllianceStructureController extends BaseController
{
    private AllianceStructureService $structureService;
    private UserRepository $userRepo; // For permission checks
    private AllianceRoleRepository $roleRepo; // For permission checks

    public function __construct()
    {
        parent::__construct();
        $this->structureService = new AllianceStructureService();
        
        $db = Database::getInstance();
        $this->userRepo = new UserRepository($db);
        $this->roleRepo = new AllianceRoleRepository($db);
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
        
        // Get all data (definitions, costs, levels) from the service
        $data = $this->structureService->getStructureData($allianceId);
        
        // Pass permission data to the view
        $data['canManage'] = ($role && $role->can_manage_structures);

        // Set layout mode
        $data['layoutMode'] = 'full';

        $this->render('alliance/structures.php', $data + ['title' => 'Alliance Structures']);
    }

    /**
     * Handles the structure upgrade form submission.
     */
    public function handleUpgrade(): void
    {
        // 1. Validate CSRF token
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/structures');
            return;
        }
        
        // 2. Get data from form
        $userId = $this->session->get('user_id');
        $structureKey = (string)($_POST['structure_key'] ?? '');

        // 3. Call the service (it handles all logic and flash messages)
        $this->structureService->purchaseOrUpgradeStructure($userId, $structureKey);
        
        // 4. Redirect back
        $this->redirect('/alliance/structures');
    }
}