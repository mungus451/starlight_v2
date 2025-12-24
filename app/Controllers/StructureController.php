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
            'groupedStructures' => $groupedStructures
        ];

        if ($this->session->get('is_mobile')) {
            $this->render('structures/mobile_show.php', $viewData);
        } else {
            $this->render('structures/show.php', $viewData);
        }
    }

    public function handleUpgrade(): void
    {
        // ... (existing code is correct)
    }

    public function handleBatchUpgrade(): void
    {
        // ... (existing code is correct)
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

        // Find the correct category key by matching slugs
        $categoryData = null;
        $originalCategoryKey = null;
        foreach (array_keys($groupedStructures) as $key) {
            if ($this->slugify($key) === $categorySlug) {
                $originalCategoryKey = $key;
                break;
            }
        }

        if (!$originalCategoryKey) {
            $this->jsonResponse(['error' => 'Invalid category specified'], 404);
            return;
        }
        
        $categoryData = $groupedStructures[$originalCategoryKey];

        $viewData = [
            'structures' => $categoryData,
            'csrf_token' => $this->csrfService->generateToken()
        ];
        
        ob_start();
        extract($viewData);
        require __DIR__ . '/../../views/structures/partials/_mobile_structure_category.php';
        $html = ob_get_clean();

        $this->jsonResponse(['html' => $html]);
    }

    private function slugify(string $text): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text)));
    }
}