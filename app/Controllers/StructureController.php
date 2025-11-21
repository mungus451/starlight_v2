<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Models\Services\StructureService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles all HTTP requests for the Structures page.
 * * Refactored for Strict Dependency Injection.
 */
class StructureController extends BaseController
{
    private StructureService $structureService;

    /**
     * DI Constructor.
     *
     * @param StructureService $structureService
     * @param Session $session
     * @param CSRFService $csrfService
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        StructureService $structureService,
        Session $session,
        CSRFService $csrfService,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $levelCalculator, $statsRepo);
        $this->structureService = $structureService;
    }

    /**
     * Displays the main structures page.
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        
        $data = $this->structureService->getStructureData($userId);

        $data['layoutMode'] = 'full';

        $this->render('structures/show.php', $data + ['title' => 'Structures']);
    }

    /**
     * Handles the structure upgrade form submission.
     */
    public function handleUpgrade(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/structures');
            return;
        }
        
        $userId = $this->session->get('user_id');
        $structureKey = (string)($_POST['structure_key'] ?? '');

        $this->structureService->upgradeStructure($userId, $structureKey);
        
        $this->redirect('/structures');
    }
}