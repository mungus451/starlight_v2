<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\TrainingService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles all HTTP requests for the Training page.
 * * Refactored for Strict Dependency Injection & Centralized Validation.
 * * Decoupled: Consumes ServiceResponse.
 */
class TrainingController extends BaseController
{
    private TrainingService $trainingService;

    /**
     * DI Constructor.
     *
     * @param TrainingService $trainingService
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        TrainingService $trainingService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        // Pass mandatory base dependencies to the parent
        parent::__construct($session, $csrfService, $validator, $levelCalculator, $statsRepo);
        $this->trainingService = $trainingService;
    }

    /**
     * Displays the main training page.
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        
        $data = $this->trainingService->getTrainingData($userId);

        $this->render('training/show.php', $data + [
            'title' => 'Training',
            'layoutMode' => 'full'
        ]);
    }

    /**
     * Handles the training form submission.
     */
    public function handleTrain(): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'unit_type' => 'required|string',
            'amount' => 'required|int|min:1'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/training');
            return;
        }

        // 3. Execute Logic
        $userId = $this->session->get('user_id');
        $response = $this->trainingService->trainUnits($userId, $data['unit_type'], $data['amount']);
        
        // 4. Handle Response
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/training');
    }
}