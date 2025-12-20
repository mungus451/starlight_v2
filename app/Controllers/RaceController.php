<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Core\JsonResponse;
use App\Models\Services\ViewContextService;
use App\Models\Services\RaceService;
use App\Core\Exceptions\RedirectException;

/**
 * Handles race selection API routes.
 */
class RaceController extends BaseController
{
    private RaceService $raceService;

    public function __construct(
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService,
        RaceService $raceService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->raceService = $raceService;
    }

    /**
     * GET /api/races
     * Returns all available races as JSON.
     */
    public function getRaces(): void
    {
        try {
            $races = $this->raceService->getAllRaces();
            
            // Convert to array format for JSON
            $racesData = array_map(function($race) {
                return [
                    'id' => $race->id,
                    'name' => $race->name,
                    'exclusive_resource' => $race->exclusive_resource,
                    'lore' => $race->lore,
                    'uses' => $race->uses
                ];
            }, $races);

            JsonResponse::success($racesData);
        } catch (\Exception $e) {
            JsonResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/race/select
     * Allows a user to select their race (one-time only).
     */
    public function selectRace(): void
    {
        // Validate CSRF token
        $this->csrfService->validateToken($_POST['csrf_token'] ?? '');

        // Get user ID from session
        $userId = $this->session->get('user_id');
        if (!$userId) {
            JsonResponse::error('You must be logged in.', 401);
            return;
        }

        // Validate input
        $validated = $this->validate($_POST, [
            'race_id' => 'required|integer|min:1|max:5'
        ]);

        try {
            // Attempt to select race
            $this->raceService->selectRace($userId, (int)$validated['race_id']);
            
            JsonResponse::success([
                'message' => 'Race selected successfully!'
            ]);
        } catch (\Exception $e) {
            JsonResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * GET /api/race/check
     * Checks if the current user needs to select a race.
     */
    public function checkRaceStatus(): void
    {
        $userId = $this->session->get('user_id');
        if (!$userId) {
            JsonResponse::error('You must be logged in.', 401);
            return;
        }

        try {
            $needsSelection = $this->raceService->needsRaceSelection($userId);
            
            JsonResponse::success([
                'needs_selection' => $needsSelection
            ]);
        } catch (\Exception $e) {
            JsonResponse::error($e->getMessage(), 500);
        }
    }
}
