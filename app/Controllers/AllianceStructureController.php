<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\AllianceStructureService;
use App\Models\Services\ViewContextService; // --- NEW DEPENDENCY ---

/**
 * Handles all HTTP requests for the Alliance Structures page.
 * * Refactored Phase 1.3.2: Strict MVC Compliance.
 * * Fixed: Updated parent constructor call to use ViewContextService.
 */
class AllianceStructureController extends BaseController
{
    private AllianceStructureService $structureService;

    public function __construct(
        AllianceStructureService $structureService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService // --- REPLACES LevelCalculator & StatsRepo ---
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->structureService = $structureService;
    }

    /**
     * Displays the main alliance structures page.
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        
        $response = $this->structureService->getStructurePageData($userId);
        
        if (!$response->isSuccess()) {
            $this->session->setFlash('error', $response->message);
            $this->redirect('/alliance/list');
            return;
        }
        
        $data = $response->data;
        $data['layoutMode'] = 'full';

        $this->render('alliance/structures.php', $data + ['title' => 'Alliance Structures']);
    }

    /**
     * Handles the structure upgrade form submission.
     */
    public function handleUpgrade(): void
    {
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'structure_key' => 'required|string'
        ]);

        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/structures');
            return;
        }
        
        $userId = $this->session->get('user_id');
        $response = $this->structureService->purchaseOrUpgradeStructure($userId, $data['structure_key']);
        
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/alliance/structures');
    }
}