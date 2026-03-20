<?php

namespace App\Controllers;

use App\Core\Config;
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
    private Config $config;
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
        Config $config,
        StructureService $structureService,
        StructurePresenter $presenter,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->config = $config;
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
            'groupedStructures' => $groupedStructures,
            'spa_structures_enabled' => (bool)$this->config->get('spa.structures_enabled', false),
        ]);
    }

    public function apiData(): void
    {
        $userId = (int)$this->session->get('user_id', 0);
        if ($userId <= 0) {
            $this->jsonResponse(['error' => 'Not authenticated'], 401);
            return;
        }

        $rawData = $this->structureService->getStructureData($userId);
        $groupedStructures = $this->presenter->present($rawData);
        $resources = $rawData['resources'];

        $this->jsonResponse([
            'resources' => [
                'credits' => (int)$resources->credits,
            ],
            'categories' => $groupedStructures,
        ]);
    }

    public function apiUpgrade(): void
    {
        $userId = (int)$this->session->get('user_id', 0);
        if ($userId <= 0) {
            $this->jsonResponse(['error' => 'Not authenticated'], 401);
            return;
        }

        $structureKey = (string)($_POST['structure_key'] ?? '');
        if ($structureKey === '') {
            $this->jsonResponse(['error' => 'Invalid structure type.'], 400);
            return;
        }

        $response = $this->structureService->upgradeStructure($userId, $structureKey);
        if (!$response->isSuccess()) {
            $this->jsonResponse(['error' => $response->message], 400);
            return;
        }

        $payload = ['success' => true, 'message' => $response->message];
        if (isset($response->data['new_level'])) {
            $payload['new_level'] = (int)$response->data['new_level'];
        }
        if (isset($response->data['cost'])) {
            $payload['cost'] = (int)$response->data['cost'];
        }

        $this->jsonResponse($payload);
    }

    public function apiBatchUpgrade(): void
    {
        $userId = (int)$this->session->get('user_id', 0);
        if ($userId <= 0) {
            $this->jsonResponse(['error' => 'Not authenticated'], 401);
            return;
        }

        $rawKeys = $_POST['structure_keys'] ?? [];
        if (is_string($rawKeys)) {
            $decoded = json_decode($rawKeys, true);
            $structureKeys = is_array($decoded) ? $decoded : [];
        } elseif (is_array($rawKeys)) {
            $structureKeys = $rawKeys;
        } else {
            $structureKeys = [];
        }

        if (empty($structureKeys)) {
            $this->jsonResponse(['error' => 'No structures selected for batch upgrade.'], 400);
            return;
        }

        $response = $this->structureService->processBatchUpgrade($userId, $structureKeys);
        if (!$response->isSuccess()) {
            $this->jsonResponse(['error' => $response->message], 400);
            return;
        }

        $payload = ['success' => true, 'message' => $response->message];
        if (isset($response->data['total_cost'])) {
            $payload['total_cost'] = (int)$response->data['total_cost'];
        }

        $this->jsonResponse($payload);
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
