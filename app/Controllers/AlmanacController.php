<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\ViewContextService;
use App\Models\Services\AlmanacService;

/**
 * Controller for the Almanac (Dossier View).
 * Handles the main view and AJAX endpoints for single-entity stats.
 */
class AlmanacController extends BaseController
{
    private AlmanacService $almanacService;

    public function __construct(
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService,
        AlmanacService $almanacService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->almanacService = $almanacService;
    }

    /**
     * Renders the main Almanac page.
     */
    public function index(): void
    {
        $players = $this->almanacService->getAllPlayersList();
        $alliances = $this->almanacService->getAllAlliancesList();

        $this->render('almanac/index.php', [
            'pageTitle' => 'The Almanac',
            'players' => $players,
            'alliances' => $alliances
        ]);
    }

    /**
     * AJAX: Search for players (autocomplete).
     */
    public function searchPlayers(): void
    {
        $query = $_GET['q'] ?? '';
        $results = $this->almanacService->searchPlayers($query);
        $this->jsonResponse($results);
    }

    /**
     * AJAX: Search for alliances (autocomplete).
     */
    public function searchAlliances(): void
    {
        $query = $_GET['q'] ?? '';
        $results = $this->almanacService->searchAlliances($query);
        $this->jsonResponse($results);
    }

    /**
     * AJAX: Get Single Player Dossier.
     */
    public function getPlayerDossier(): void
    {
        $playerId = (int)($_GET['player_id'] ?? 0);

        if ($playerId === 0) {
            $this->jsonResponse(['error' => 'Invalid player ID'], 400);
            return;
        }

        $data = $this->almanacService->getPlayerDossier($playerId);

        if (!$data) {
            $this->jsonResponse(['error' => 'Player not found'], 404);
            return;
        }

        $this->jsonResponse($data);
    }

    /**
     * AJAX: Get Single Alliance Dossier.
     */
    public function getAllianceDossier(): void
    {
        $allianceId = (int)($_GET['alliance_id'] ?? 0);

        if ($allianceId === 0) {
            $this->jsonResponse(['error' => 'Invalid alliance ID'], 400);
            return;
        }

        $data = $this->almanacService->getAllianceDossier($allianceId);

        if (!$data) {
            $this->jsonResponse(['error' => 'Alliance not found'], 404);
            return;
        }

        $this->jsonResponse($data);
    }
}
