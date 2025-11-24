<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\ArmoryService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\ResourceRepository; // Added for AJAX state refresh
use App\Models\Repositories\ArmoryRepository;   // Added for AJAX state refresh

/**
 * Handles all HTTP requests for the Armory.
 * * Refactored to support AJAX interactions for Manufacture and Equip.
 */
class ArmoryController extends BaseController
{
    private ArmoryService $armoryService;
    private ResourceRepository $resourceRepo;
    private ArmoryRepository $armoryRepo;

    /**
     * DI Constructor.
     *
     * @param ArmoryService $armoryService
     * @param ResourceRepository $resourceRepo
     * @param ArmoryRepository $armoryRepo
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        ArmoryService $armoryService,
        ResourceRepository $resourceRepo,
        ArmoryRepository $armoryRepo,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $validator, $levelCalculator, $statsRepo);
        $this->armoryService = $armoryService;
        $this->resourceRepo = $resourceRepo;
        $this->armoryRepo = $armoryRepo;
    }

    /**
     * Displays the main Armory page with all tabs and items.
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        $data = $this->armoryService->getArmoryData($userId);
        
        $data['title'] = 'Armory';
        $data['layoutMode'] = 'full';

        $this->render('armory/show.php', $data);
    }

    /**
     * Handles the "Manufacture / Upgrade" form submission.
     * Supports both standard POST (Redirect) and AJAX (JSON).
     */
    public function handleManufacture(): void
    {
        $isJson = $this->wantsJson();
        $rules = [
            'csrf_token' => 'required',
            'item_key' => 'required|string',
            'quantity' => 'required|int|min:1'
        ];

        // 1. Validation
        if ($isJson) {
            // Manual validation for AJAX to avoid Redirect
            $val = $this->validator->make($_POST, $rules);
            if ($val->fails()) {
                $this->jsonResponse(['success' => false, 'error' => implode(' ', $val->errors())]);
                return;
            }
            $data = $val->validated();
            
            // CSRF Check
            if (!$this->csrfService->validateToken($data['csrf_token'])) {
                $this->jsonResponse(['success' => false, 'error' => 'Invalid security token.']);
                return;
            }
        } else {
            // Standard Redirect Validation
            $data = $this->validate($_POST, $rules);
            if (!$this->csrfService->validateToken($data['csrf_token'])) {
                $this->session->setFlash('error', 'Invalid security token.');
                $this->redirect('/armory');
                return;
            }
        }

        // 2. Execute Logic
        $userId = $this->session->get('user_id');
        $success = $this->armoryService->manufactureItem($userId, $data['item_key'], $data['quantity']);
        
        // 3. Response
        if ($isJson) {
            if ($success) {
                // Fetch fresh data for the DOM update
                $resources = $this->resourceRepo->findByUserId($userId);
                $inventory = $this->armoryRepo->getInventory($userId);
                $ownedCount = $inventory[$data['item_key']] ?? 0;
                
                // Also fetch prerequisite count if applicable (frontend logic might need it, 
                // but usually just updating main item and credits is enough for 90% of cases).
                // The success message is set in Session by Service, retrieve it.
                $msg = $this->session->getFlash('success') ?? 'Manufacturing complete.';

                $this->jsonResponse([
                    'success' => true,
                    'message' => $msg,
                    'new_credits' => $resources->credits,
                    'new_owned' => $ownedCount,
                    'item_key' => $data['item_key']
                ]);
            } else {
                $error = $this->session->getFlash('error') ?? 'Manufacturing failed.';
                $this->jsonResponse(['success' => false, 'error' => $error]);
            }
        } else {
            $this->redirect('/armory');
        }
    }

    /**
     * Handles the "Equip" form submission.
     */
    public function handleEquip(): void
    {
        $isJson = $this->wantsJson();
        $rules = [
            'csrf_token' => 'required',
            'unit_key' => 'required|string',
            'category_key' => 'required|string',
            'item_key' => 'nullable|string'
        ];

        // 1. Validation
        if ($isJson) {
            $val = $this->validator->make($_POST, $rules);
            if ($val->fails()) {
                $this->jsonResponse(['success' => false, 'error' => implode(' ', $val->errors())]);
                return;
            }
            $data = $val->validated();
            
            if (!$this->csrfService->validateToken($data['csrf_token'])) {
                $this->jsonResponse(['success' => false, 'error' => 'Invalid security token.']);
                return;
            }
        } else {
            $data = $this->validate($_POST, $rules);
            if (!$this->csrfService->validateToken($data['csrf_token'])) {
                $this->session->setFlash('error', 'Invalid security token.');
                $this->redirect('/armory');
                return;
            }
        }

        // 2. Execute Logic
        $userId = $this->session->get('user_id');
        $itemKey = $data['item_key'] ?? '';

        $success = $this->armoryService->equipItem($userId, $data['unit_key'], $data['category_key'], $itemKey);
        
        // 3. Response
        if ($isJson) {
            if ($success) {
                $msg = $this->session->getFlash('success') ?? 'Loadout updated.';
                $this->jsonResponse([
                    'success' => true,
                    'message' => $msg
                ]);
            } else {
                $error = $this->session->getFlash('error') ?? 'Failed to equip item.';
                $this->jsonResponse(['success' => false, 'error' => $error]);
            }
        } else {
            $this->redirect('/armory');
        }
    }

    /**
     * Helper to detect JSON requests (AJAX).
     */
    private function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        // Also check specific X-Requested-With header often sent by JS fetch wrappers
        $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        
        return str_contains($accept, 'application/json') || $requestedWith === 'XMLHttpRequest';
    }

    /**
     * Helper to send JSON response and exit.
     */
    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}