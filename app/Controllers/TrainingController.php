<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\TrainingService;
use App\Models\Services\ViewContextService; // --- NEW DEPENDENCY ---

/**
 * Handles all HTTP requests for the Training page.
 * * Refactored for Strict Dependency Injection & Centralized Validation.
 * * Fixed: Updated parent constructor call to use ViewContextService.
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
     * @param ViewContextService $viewContextService // --- REPLACES LevelCalculator & StatsRepo ---
     */
    public function __construct(
        TrainingService $trainingService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService
    ) {
        // Pass mandatory base dependencies to the parent
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->trainingService = $trainingService;
    }

    /**
     * Displays the main training page.
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        
        $data = $this->trainingService->getTrainingData($userId);
        
        $viewData = $data + [
            'title' => 'Training',
            'layoutMode' => 'full',
            'csrf_token' => $this->csrfService->generateToken()
        ];

        if ($this->session->get('is_mobile')) {
            $this->render('training/mobile_show.php', $viewData);
        } else {
            $this->render('training/show.php', $viewData);
        }
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
} /* test commit */