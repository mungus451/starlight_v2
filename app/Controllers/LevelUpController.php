<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Models\Services\LevelUpService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles all HTTP requests for the Level Up page.
 * * Refactored for Strict Dependency Injection.
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
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        LevelUpService $levelUpService,
        Session $session,
        CSRFService $csrfService,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $levelCalculator, $statsRepo);
        $this->levelUpService = $levelUpService;
    }

    /**
     * Displays the main level up page.
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        $data = $this->levelUpService->getLevelUpData($userId);

        $this->render('level_up/show.php', $data + ['title' => 'Level Up']);
    }

    /**
     * Handles the spend points form submission.
     */
    public function handleSpend(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/level-up');
            return;
        }

        $userId = $this->session->get('user_id');
        $strength = (int)($_POST['strength'] ?? 0);
        $constitution = (int)($_POST['constitution'] ?? 0);
        $wealth = (int)($_POST['wealth'] ?? 0);
        $dexterity = (int)($_POST['dexterity'] ?? 0);
        $charisma = (int)($_POST['charisma'] ?? 0);

        $this->levelUpService->spendPoints(
            $userId,
            $strength,
            $constitution,
            $wealth,
            $dexterity,
            $charisma
        );
        
        $this->redirect('/level-up');
    }
}