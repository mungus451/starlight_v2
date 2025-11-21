<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Models\Services\TrainingService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles all HTTP requests for the Training page.
 * * Refactored for Strict Dependency Injection.
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
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        TrainingService $trainingService,
        Session $session,
        CSRFService $csrfService,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        // Pass mandatory base dependencies to the parent
        parent::__construct($session, $csrfService, $levelCalculator, $statsRepo);
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
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/training');
            return;
        }

        $userId = $this->session->get('user_id');
        $unitType = (string)($_POST['unit_type'] ?? '');
        $amount = (int)($_POST['amount'] ?? 0);

        $this->trainingService->trainUnits($userId, $unitType, $amount);
        
        $this->redirect('/training');
    }
}