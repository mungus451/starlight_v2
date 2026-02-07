<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\TrainingService;
use App\Models\Services\ViewContextService;
use App\Presenters\TrainingPresenter; // --- NEW DEPENDENCY ---

/**
 * Handles all HTTP requests for the Training page.
 * * Refactored for Strict Dependency Injection & Centralized Validation.
 * * Fixed: Updated parent constructor call to use ViewContextService.
 */
class TrainingController extends BaseController
{
    private TrainingService $trainingService;
    private TrainingPresenter $presenter; // --- NEW DEPENDENCY ---

    /**
     * DI Constructor.
     */
    public function __construct(
        TrainingService $trainingService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService,
        TrainingPresenter $presenter // --- NEW DEPENDENCY ---
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->trainingService = $trainingService;
        $this->presenter = $presenter; // --- NEW DEPENDENCY ---
    }

    /**
     * Displays the main training page.
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        
        $serviceData = $this->trainingService->getTrainingData($userId);

        // Add the CSRF token to the data for the presenter
        $serviceData['csrf_token'] = $this->csrfService->generateToken();
        
        // Use the presenter to prepare data for the view
        $viewData = $this->presenter->present($serviceData);

        $this->render('training/show.php', $viewData + [
            'title' => 'Training'
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
            'units' => 'required|array'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/training');
            return;
        }

        // 3. Execute Logic
        $userId = $this->session->get('user_id');
        $unitsToTrain = array_filter($data['units'], fn($amount) => (int)$amount > 0);

        if (empty($unitsToTrain)) {
            $this->session->setFlash('error', 'You did not select any units to train.');
            $this->redirect('/training');
            return;
        }

        // Note: TrainingService would need to be updated to handle an array of units
        foreach ($unitsToTrain as $unitType => $amount) {
            $response = $this->trainingService->trainUnits($userId, $unitType, (int)$amount);
            if (!$response->isSuccess()) {
                $this->session->setFlash('error', $response->message);
                $this->redirect('/training');
                return;
            }
        }
        
        // 4. Handle Response
        $this->session->setFlash('success', 'Units have been queued for training.');
        $this->redirect('/training');
    }
}