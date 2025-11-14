<?php

namespace App\Controllers; // Correct namespace

use App\Models\Services\ArmoryService;

/**
 * Handles all HTTP requests for the Armory.
 */
class ArmoryController extends BaseController
{
    private ArmoryService $armoryService;

    public function __construct()
    {
        parent::__construct();
        // Instantiate the new service
        $this->armoryService = new ArmoryService();
    }

    /**
     * Displays the main Armory page with all tabs and items.
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        
        // --- THIS IS NOW ACTIVE ---
        // Get all data (config, resources, structures, inventory, loadouts)
        $data = $this->armoryService->getArmoryData($userId);
        
        // Add title and set layout mode
        $data['title'] = 'Armory';
        $data['layoutMode'] = 'full'; // Use the full-width layout

        // We will create 'armory/show.php' in the next phase
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

        // --- THIS IS NOW ACTIVE ---
        // Call the service, which handles all logic and flash messages
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

        // --- THIS IS NOW ACTIVE ---
        // Call the service, which handles all logic and flash messages
        $this->armoryService->equipItem($userId, $unitKey, $categoryKey, $itemKey);
        
        $this->redirect('/armory');
    }
}