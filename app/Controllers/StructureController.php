<?php

namespace App\Controllers;

use App\Models\Services\StructureService;

/**
 * Handles all HTTP requests for the Structures page.
 */
class StructureController extends BaseController
{
    private StructureService $structureService;

    public function __construct()
    {
        parent::__construct();
        $this->structureService = new StructureService();
    }

    /**
     * Displays the main structures page.
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        
        // Get all data (structures, resources, costs) from the service
        $data = $this->structureService->getStructureData($userId);

        // NEW: Tell the layout to render in full-width mode
        $data['layoutMode'] = 'full';

        $this->render('structures/show.php', $data + ['title' => 'Structures']);
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
            $this->redirect('/structures');
            return;
        }
        
        // 2. Get data from form
        $userId = $this->session->get('user_id');
        $structureKey = (string)($_POST['structure_key'] ?? '');

        // 3. Call the service (it handles all logic and flash messages)
        $this->structureService->upgradeStructure($userId, $structureKey);
        
        // 4. Redirect back
        $this->redirect('/structures');
    }
}