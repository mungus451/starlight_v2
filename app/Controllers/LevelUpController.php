<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\LevelUpService;
use App\Models\Services\ViewContextService; // --- NEW DEPENDENCY ---

/**
 * Handles all HTTP requests for the Level Up page.
 * * Refactored for Strict Dependency Injection & Centralized Validation.
 * * Decoupled: Consumes ServiceResponse.
 * * Fixed: Updated parent constructor call to use ViewContextService.
 */
class LevelUpController extends BaseController
{
    private LevelUpService $levelUpService;

    /**
     * DI Constructor.
     *
     * @param LevelUpService $levelUpService
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param ViewContextService $viewContextService // --- REPLACES LevelCalculator & StatsRepo ---
     */
    public function __construct(
        LevelUpService $levelUpService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->levelUpService = $levelUpService;
    }

    /**
     * Displays the main level up page.
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        $data = $this->levelUpService->getLevelUpData($userId);
        
        $viewData = $data + [
            'title' => 'Level Up',
            'csrf_token' => $this->csrfService->generateToken()
        ];

        if ($this->session->get('is_mobile')) {
            $this->render('level_up/mobile_show.php', $viewData);
        } else {
            $this->render('level_up/show.php', $viewData);
        }
    }

    /**
     * Handles the spend points form submission.
     */
    public function handleSpend(): void
    {
        // 1. Validate Input (Strict Non-Negative Integers)
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'strength' => 'required|int|min:0',
            'constitution' => 'required|int|min:0',
            'wealth' => 'required|int|min:0',
            'dexterity' => 'required|int|min:0',
            'charisma' => 'required|int|min:0',
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/level-up');
            return;
        }

        // 3. Execute Logic
        $userId = $this->session->get('user_id');
        
        $response = $this->levelUpService->spendPoints(
            $userId,
            $data['strength'],
            $data['constitution'],
            $data['wealth'],
            $data['dexterity'],
            $data['charisma']
        );
        
        // 4. Handle Response
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/level-up');
    }
}