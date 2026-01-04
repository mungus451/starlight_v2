<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\StructureService;
use App\Presenters\StructurePresenter;
use App\Models\Services\ViewContextService; // --- NEW DEPENDENCY ---

/**
 * Handles all HTTP requests for the Personal Structures page.
 * * Refactored Phase 2.5: Integrates StructurePresenter to fix View variables.
 * * Fixed: Updated parent constructor call to use ViewContextService.
 */
class StructureController extends BaseController
{
    private StructureService $structureService;
    private StructurePresenter $presenter;

    /**
     * DI Constructor.
     *
     * @param StructureService $structureService
     * @param StructurePresenter $presenter
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param ViewContextService $viewContextService // --- REPLACES LevelCalculator & StatsRepo ---
     */
    public function __construct(
        StructureService $structureService,
        StructurePresenter $presenter,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->structureService = $structureService;
        $this->presenter = $presenter;
    }

    /**
     * Displays the main structures page.
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        
        // 1. Get raw data from Service
        $rawData = $this->structureService->getStructureData($userId);

        // 2. Pass raw data to Presenter to get View-Ready data ($groupedStructures)
        $groupedStructures = $this->presenter->present($rawData);

        // 3. Render View
        // We pass 'resources' explicitly as it's used in the header stats card
        $this->render('structures/show.php', [
            'title' => 'Structures',
            'layoutMode' => 'full',
            'resources' => $rawData['resources'], 
            'groupedStructures' => $groupedStructures
        ]);
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
            $this->redirect('/structures');
            return;
        }
        
        $userId = $this->session->get('user_id');
        $response = $this->structureService->upgradeStructure($userId, $data['structure_key']);
        
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/structures');
    }

    /**
     * Handles batch structure upgrades.
     */
    public function handleBatchUpgrade(): void
    {
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'structure_keys' => 'required' 
        ]);

        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/structures');
            return;
        }
        
        // Expecting JSON string from the frontend checkout form
        // We use $_POST directly because the Validator sanitizes strings with htmlspecialchars, which breaks JSON.
        $keys = json_decode($_POST['structure_keys'], true);
        
        if (!is_array($keys) || empty($keys)) {
             $this->session->setFlash('error', 'No structures selected for batch upgrade.');
             $this->redirect('/structures');
             return;
        }

        $userId = $this->session->get('user_id');
        $response = $this->structureService->processBatchUpgrade($userId, $keys);
        
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/structures');
    }

    /**
     * AJAX endpoint for fetching mobile structure category data.
     */
    public function getMobileStructureTabData(array $params): void
    {
        $categorySlug = $params['category'] ?? '';

        $userId = $this->session->get('user_id');
        if (is_null($userId)) {
            $this->jsonResponse(['error' => 'Not authenticated'], 401);
            return;
        }

        $rawData = $this->structureService->getStructureData($userId);
        $groupedStructures = $this->presenter->present($rawData);

        $categoryData = null;
        foreach ($groupedStructures as $categoryName => $data) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $categoryName)));
            if ($slug === $categorySlug) {
                $categoryData = $data;
                break;
            }
        }

        if (is_null($categoryData)) {
            $this->jsonResponse(['error' => 'Invalid category specified: ' . $categorySlug], 404);
            return;
        }

        // Pass necessary data to the partial view
        $viewData = [
            'structures' => $categoryData,
            'csrf_token' => $this->csrfService->generateToken()
        ];
        
        ob_start();
        extract($viewData);
        require __DIR__ . '/../../views/mobile/structures/partials/structure_category.php';
        $html = ob_get_clean();

        $this->jsonResponse(['html' => $html]);
    }
}