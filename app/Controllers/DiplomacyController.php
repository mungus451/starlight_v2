<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\DiplomacyService;
use App\Models\Services\ViewContextService; // --- NEW DEPENDENCY ---

/**
 * Handles all HTTP requests for the Alliance Diplomacy page.
 * * Refactored Phase 1.2: Removed direct Repository dependencies.
 * * Fixed: Updated parent constructor call to use ViewContextService.
 */
class DiplomacyController extends BaseController
{
    private DiplomacyService $diplomacyService;

    public function __construct(
        DiplomacyService $diplomacyService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService // --- REPLACES LevelCalculator & StatsRepo ---
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->diplomacyService = $diplomacyService;
    }

    /**
     * Displays the main diplomacy page.
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        
        $response = $this->diplomacyService->getDiplomacyData($userId);

        if (!$response->isSuccess()) {
            $this->session->setFlash('error', $response->message);
            $this->redirect('/alliance/list');
            return;
        }

        $data = $response->data;
        $data['layoutMode'] = 'full';
        $data['title'] = 'Alliance Diplomacy';

        $this->render('alliance/diplomacy.php', $data);
    }

    /**
     * Handles proposing a new treaty.
     */
    public function handleProposeTreaty(): void
    {
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'target_alliance_id' => 'required|int',
            'treaty_type' => 'required|string|in:peace,non_aggression,mutual_defense',
            'terms' => 'nullable|string|max:5000'
        ]);

        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/diplomacy');
            return;
        }

        $userId = $this->session->get('user_id');

        $response = $this->diplomacyService->proposeTreaty(
            $userId, 
            $data['target_alliance_id'], 
            $data['treaty_type'], 
            $data['terms'] ?? ''
        );
        
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/alliance/diplomacy');
    }

    /**
     * Handles accepting a treaty.
     */
    public function handleAcceptTreaty(array $vars): void
    {
        $data = $this->validate($_POST, [
            'csrf_token' => 'required'
        ]);

        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/diplomacy');
            return;
        }

        $userId = $this->session->get('user_id');
        $treatyId = (int)($vars['id'] ?? 0);
        
        $response = $this->diplomacyService->acceptTreaty($userId, $treatyId);
        
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/alliance/diplomacy');
    }

    /**
     * Handles declining a treaty proposal.
     */
    public function handleDeclineTreaty(array $vars): void
    {
        $data = $this->validate($_POST, ['csrf_token' => 'required']);
        
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/diplomacy');
            return;
        }

        $userId = $this->session->get('user_id');
        $treatyId = (int)($vars['id'] ?? 0);
        
        $response = $this->diplomacyService->endTreaty($userId, $treatyId, 'decline');
        
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/alliance/diplomacy');
    }

    /**
     * Handles breaking an active treaty.
     */
    public function handleBreakTreaty(array $vars): void
    {
        $data = $this->validate($_POST, ['csrf_token' => 'required']);
        
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/diplomacy');
            return;
        }

        $userId = $this->session->get('user_id');
        $treatyId = (int)($vars['id'] ?? 0);
        
        $response = $this->diplomacyService->endTreaty($userId, $treatyId, 'break');
        
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/alliance/diplomacy');
    }

    /**
     * Handles declaring a rivalry.
     */
    public function handleDeclareRivalry(): void
    {
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'target_alliance_id' => 'required|int'
        ]);

        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/diplomacy');
            return;
        }

        $userId = $this->session->get('user_id');

        $response = $this->diplomacyService->declareRivalry($userId, $data['target_alliance_id']);
        
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/alliance/diplomacy');
    }
}