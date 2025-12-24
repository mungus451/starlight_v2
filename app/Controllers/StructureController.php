<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\StructureService;
use App\Presenters\StructurePresenter;
use App\Models\Services\ViewContextService;

class StructureController extends BaseController
{
    private StructureService $structureService;
    private StructurePresenter $presenter;

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

    public function show(): void
    {
        $userId = $this->session->get('user_id');
        
        $rawData = $this->structureService->getStructureData($userId);
        $groupedStructures = $this->presenter->present($rawData);

        $viewData = [
            'title' => 'Structures',
            'layoutMode' => 'full',
            'resources' => $rawData['resources'], 
            'groupedStructures' => $groupedStructures,
            'csrf_token' => $this->csrfService->generateToken() // Ensure token for initial load
        ];

        if ($this->session->get('is_mobile')) {
            $this->render('structures/mobile_show.php', $viewData);
        } else {
            $this->render('structures/show.php', $viewData);
        }
    }

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
        foreach (array_keys($groupedStructures) as $key) {
            if (strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $key))) === $categorySlug) {
                $categoryData = $groupedStructures[$key];
                break;
            }
        }

        if (is_null($categoryData)) {
            $this->jsonResponse(['error' => 'Invalid category specified'], 404);
            return;
        }

        // Pass only the necessary data to the partial view
        $viewData = [
            'structures' => $categoryData
        ];
        
        ob_start();
        extract($viewData);
        require __DIR__ . '/../../views/structures/partials/_mobile_structure_category.php';
        $html = ob_get_clean();

        $this->jsonResponse(['html' => $html]);
    }
}