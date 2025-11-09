<?php

namespace App\Controllers;

use App\Models\Services\TrainingService;

/**
 * Handles all HTTP requests for the Training page.
 */
class TrainingController extends BaseController
{
    private TrainingService $trainingService;

    public function __construct()
    {
        parent::__construct();
        $this->trainingService = new TrainingService();
    }

    /**
     * Displays the main training page.
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        
        // Get both resources and costs from the service
        $data = $this->trainingService->getTrainingData($userId);

        $this->render('training/show.php', $data + ['title' => 'Training']);
    }

    /**
     * Handles the training form submission.
     */
    public function handleTrain(): void
    {
        // 1. Validate CSRF token
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this.redirect('/training');
            return;
        }

        // 2. Get data from form
        $userId = $this->session->get('user_id');
        $unitType = (string)($_POST['unit_type'] ?? '');
        $amount = (int)($_POST['amount'] ?? 0);

        // 3. Call the service (it handles all logic and flash messages)
        $this->trainingService->trainUnits($userId, $unitType, $amount);
        
        // 4. Redirect back to the training page
        $this.redirect('/training');
    }
}