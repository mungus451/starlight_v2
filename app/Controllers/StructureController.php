<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\StructureService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;
use App\Presenters\StructurePresenter;

/**
 * Handles all HTTP requests for the Personal Structures page.
 * * Refactored Phase 2.5: Integrates StructurePresenter to fix View variables.
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
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        StructureService $structureService,
        StructurePresenter $presenter,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $validator, $levelCalculator, $statsRepo);
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
}