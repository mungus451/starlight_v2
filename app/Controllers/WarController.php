<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\WarService;
use App\Models\Services\ViewContextService; // --- NEW DEPENDENCY ---

/**
 * Handles all HTTP requests for the Alliance War page.
 * * Refactored Phase 1.3: Strict MVC Compliance.
 * * Fixed: Updated parent constructor call to use ViewContextService.
 */
class WarController extends BaseController
{
    private WarService $warService;

    public function __construct(
        WarService $warService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService // --- REPLACES LevelCalculator & StatsRepo ---
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->warService = $warService;
    }

    /**
     * Displays the main war page (active wars, history, declare).
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        
        $response = $this->warService->getWarPageData($userId);

        if (!$response->isSuccess()) {
            $this->session->setFlash('error', $response->message);
            $this->redirect('/alliance/list');
            return;
        }

        $data = $response->data;
        $data['layoutMode'] = 'full';
        $data['title'] = 'Alliance War Room';

        $this->render('alliance/war.php', $data);
    }

    /**
     * Handles declaring a war.
     */
    public function handleDeclareWar(): void
    {
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'target_alliance_id' => 'required|int',
            'war_name' => 'required|string|min:3|max:100',
            'casus_belli' => 'nullable|string|max:1000',
            'goal_key' => 'required|string|in:credits_plundered,units_killed',
            'goal_threshold' => 'required|int|min:1'
        ]);

        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/war');
            return;
        }
        
        $userId = $this->session->get('user_id');

        $response = $this->warService->declareWar(
            $userId,
            $data['target_alliance_id'],
            $data['war_name'],
            $data['casus_belli'] ?? '',
            $data['goal_key'],
            $data['goal_threshold']
        );
        
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/alliance/war');
    }
}