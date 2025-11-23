<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\ArmoryService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles all HTTP requests for the Armory.
 * * Refactored for Strict Dependency Injection & Centralized Validation.
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
     * @param Validator $validator
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        ArmoryService $armoryService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $validator, $levelCalculator, $statsRepo);
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
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'item_key' => 'required|string',
            'quantity' => 'required|int|min:1'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/armory');
            return;
        }

        // 3. Execute Logic
        $userId = $this->session->get('user_id');
        $this->armoryService->manufactureItem($userId, $data['item_key'], $data['quantity']);
        
        $this->redirect('/armory');
    }

    /**
     * Handles the "Equip" form submission.
     */
    public function handleEquip(): void
    {
        // 1. Validate Input
        // item_key can be nullable (empty string in POST) to signify un-equipping
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'unit_key' => 'required|string',
            'category_key' => 'required|string',
            'item_key' => 'nullable|string'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/armory');
            return;
        }

        // 3. Execute Logic
        $userId = $this->session->get('user_id');
        
        // Map null (from validation) to empty string (expected by Service for un-equipping)
        $itemKey = $data['item_key'] ?? '';

        $this->armoryService->equipItem($userId, $data['unit_key'], $data['category_key'], $itemKey);
        
        $this->redirect('/armory');
    }
}