<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Models\Services\ArmoryService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles all HTTP requests for the Armory.
 * * Refactored for Strict Dependency Injection.
 */
class ArmoryController extends BaseController
{
    private ArmoryService $armoryService;

    /**
     * DI Constructor.
     *
     * @param ArmoryService $armoryService
     * @param Session $session
     * @param CSRFService $csrfService
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        ArmoryService $armoryService,
        Session $session,
        CSRFService $csrfService,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $levelCalculator, $statsRepo);
        $this->armoryService = $armoryService;
    }

    /**
     * Displays the main Armory page with all tabs and items.
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        
        // Get all data (config, resources, structures, inventory, loadouts)
        $data = $this->armoryService->getArmoryData($userId);
        
        // Add title and set layout mode
        $data['title'] = 'Armory';
        $data['layoutMode'] = 'full';

        $this->render('armory/show.php', $data);
    }

    /**
     * Handles the "Manufacture / Upgrade" form submission.
     */
    public function handleManufacture(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/armory');
            return;
        }

        $userId = $this->session->get('user_id');
        $itemKey = (string)($_POST['item_key'] ?? '');
        $quantity = (int)($_POST['quantity'] ?? 0);

        $this->armoryService->manufactureItem($userId, $itemKey, $quantity);
        
        $this->redirect('/armory');
    }

    /**
     * Handles the "Equip" form submission.
     */
    public function handleEquip(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/armory');
            return;
        }

        $userId = $this->session->get('user_id');
        $unitKey = (string)($_POST['unit_key'] ?? '');
        $categoryKey = (string)($_POST['category_key'] ?? '');
        $itemKey = (string)($_POST['item_key'] ?? '');

        $this->armoryService->equipItem($userId, $unitKey, $categoryKey, $itemKey);
        
        $this->redirect('/armory');
    }
}