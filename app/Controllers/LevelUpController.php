<?php

namespace App\Controllers;

use App\Models\Services\LevelUpService;

/**
 * Handles all HTTP requests for the Level Up page.
 */
class LevelUpController extends BaseController
{
    private LevelUpService $levelUpService;

    public function __construct()
    {
        parent::__construct();
        $this->levelUpService = new LevelUpService();
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
        // 1. Validate CSRF token
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            // --- FIX ---
            $this->session->setFlash('error', 'Invalid security token.');
            // --- FIX ---
            $this->redirect('/level-up');
            return;
        }

        // 2. Get data from form (cast to int)
        $userId = $this->session->get('user_id');
        $strength = (int)($_POST['strength'] ?? 0);
        $constitution = (int)($_POST['constitution'] ?? 0);
        $wealth = (int)($_POST['wealth'] ?? 0);
        $dexterity = (int)($_POST['dexterity'] ?? 0);
        $charisma = (int)($_POST['charisma'] ?? 0);

        // 3. Call the service
        $this->levelUpService->spendPoints(
            $userId,
            $strength,
            $constitution,
            $wealth,
            $dexterity,
            $charisma
        );
        
        // 4. Redirect back
        // --- FIX ---
        $this->redirect('/level-up');
    }
}