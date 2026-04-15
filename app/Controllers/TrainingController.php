<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\Config;
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
    private Config $config;
    private TrainingService $trainingService;
    private TrainingPresenter $presenter; // --- NEW DEPENDENCY ---

    /**
     * DI Constructor.
     */
    public function __construct(
        Config $config,
        TrainingService $trainingService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService,
        TrainingPresenter $presenter // --- NEW DEPENDENCY ---
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->config = $config;
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
            'title' => 'Training',
            'spa_training_enabled' => (bool)$this->config->get('spa.training_enabled', false),
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

    public function apiData(): void
    {
        $userId = (int)$this->session->get('user_id', 0);
        if ($userId <= 0) {
            $this->jsonResponse(['error' => 'Not authenticated'], 401);
            return;
        }

        $data = $this->trainingService->getTrainingData($userId);
        $resources = $data['resources'];
        $units = $data['units'];

        $apiUnits = [];
        foreach ($units as $key => $unit) {
            $apiUnits[] = [
                'key'      => $key,
                'name'     => $unit['name'],
                'role'     => $unit['role'],
                'desc'     => $unit['desc'],
                'credits'  => (int)($unit['credits'] ?? 0),
                'citizens' => (int)($unit['citizens'] ?? 1),
                'atk'      => (int)($unit['atk'] ?? 0),
                'def'      => (int)($unit['def'] ?? 0),
                'owned'    => (int)($resources->{$key} ?? 0),
            ];
        }

        $this->jsonResponse([
            'resources' => [
                'credits'             => (int)$resources->credits,
                'untrained_citizens'  => (int)$resources->untrained_citizens,
            ],
            'units' => $apiUnits,
        ]);
    }

    public function apiTrain(): void
    {
        $userId = (int)$this->session->get('user_id', 0);
        if ($userId <= 0) {
            $this->jsonResponse(['error' => 'Not authenticated'], 401);
            return;
        }

        $units = $_POST['units'] ?? [];
        if (!is_array($units)) {
            $this->jsonResponse(['error' => 'Invalid units data.'], 400);
            return;
        }

        $unitsToTrain = array_filter($units, fn($amount) => (int)$amount > 0);
        if (empty($unitsToTrain)) {
            $this->jsonResponse(['error' => 'You did not select any units to train.'], 400);
            return;
        }

        foreach ($unitsToTrain as $unitType => $amount) {
            $response = $this->trainingService->trainUnits($userId, (string)$unitType, (int)$amount);
            if (!$response->isSuccess()) {
                $this->jsonResponse(['error' => $response->message], 400);
                return;
            }
        }

        $this->jsonResponse(['success' => true, 'message' => 'Units have been queued for training.']);
    }
}
