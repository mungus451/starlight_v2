<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\ViewContextService;
use App\Models\Repositories\UserRepository;

/**
 * Handles race selection for players who don't have a race yet.
 */
class RaceController extends BaseController
{
    private UserRepository $userRepository;
    private array $raceConfig;

    /**
     * DI Constructor.
     */
    public function __construct(
        UserRepository $userRepository,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->userRepository = $userRepository;
        $this->raceConfig = require __DIR__ . '/../../config/races.php';
    }

    /**
     * Displays the race selection page.
     */
    public function showRaceSelection(): void
    {
        $this->render('race/select.php', [
            'title' => 'Select Your Race',
            'races' => $this->raceConfig
        ]);
    }

    /**
     * Handles the race selection form submission.
     */
    public function handleRaceSelection(): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'race' => 'required'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token. Please try again.');
            $this->redirect('/race/select');
            return;
        }

        // 3. Validate race choice
        if (!isset($this->raceConfig[$data['race']])) {
            $this->session->setFlash('error', 'Invalid race selection.');
            $this->redirect('/race/select');
            return;
        }

        // 4. Get current user ID
        $userId = $this->session->get('user_id');
        if (!$userId) {
            $this->session->setFlash('error', 'You must be logged in.');
            $this->redirect('/login');
            return;
        }

        // 5. Update user's race
        $success = $this->userRepository->updateRace($userId, $data['race']);

        if ($success) {
            $this->session->setFlash('success', 'Race selected successfully! Welcome to Starlight Dominion.');
            $this->redirect('/dashboard');
        } else {
            $this->session->setFlash('error', 'Failed to update race. Please try again.');
            $this->redirect('/race/select');
        }
    }
}
