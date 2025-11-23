<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\StructureService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles all HTTP requests for the Structures page.
 * * Refactored for Strict Dependency Injection & Centralized Validation.
 */
class StructureController extends BaseController
{
    private StructureService $structureService;

    /**
     * DI Constructor.
     *
     * @param StructureService $structureService
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        StructureService $structureService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $validator, $levelCalculator, $statsRepo);
        $this->structureService = $structureService;
    }

    /**
     * Displays the main structures page.
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        
        $data = $this->structureService->getStructureData($userId);

        $data['layoutMode'] = 'full';

        $this->render('structures/show.php', $data + ['title' => 'Structures']);
    }

    /**
     * Handles the structure upgrade form submission.
     */
    public function handleUpgrade(): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'structure_key' => 'required|string'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/structures');
            return;
        }
        
        // 3. Execute Logic
        $userId = $this->session->get('user_id');
        $this->structureService->upgradeStructure($userId, $data['structure_key']);
        
        $this->redirect('/structures');
    }
}