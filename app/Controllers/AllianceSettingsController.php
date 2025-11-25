<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\AllianceManagementService;
use App\Models\Services\ViewContextService; // --- NEW DEPENDENCY ---

/**
 * Handles alliance settings and profile configuration.
 * * Refactored to consume ServiceResponse objects.
 * * Fixed: Updated parent constructor call to use ViewContextService.
 */
class AllianceSettingsController extends BaseController
{
    private AllianceManagementService $mgmtService;

    /**
     * DI Constructor.
     *
     * @param AllianceManagementService $mgmtService
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param ViewContextService $viewContextService // --- REPLACES LevelCalculator & StatsRepo ---
     */
    public function __construct(
        AllianceManagementService $mgmtService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->mgmtService = $mgmtService;
    }

    /**
     * Handles updating the alliance's public profile (desc, image, recruitment).
     */
    public function handleUpdateProfile(): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'description' => 'nullable|string|max:1000',
            'profile_picture_url' => 'nullable|url',
            'is_joinable' => 'nullable' // Checkbox (present = "1", missing = null)
        ]);

        $allianceId = $this->session->get('alliance_id');

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }

        $adminId = $this->session->get('user_id');
        
        // Convert checkbox state to boolean
        $isJoinable = isset($data['is_joinable']) && $data['is_joinable'] == '1';

        // 3. Execute Service
        $response = $this->mgmtService->updateProfile(
            $adminId, 
            $allianceId, 
            $data['description'] ?? '', 
            $data['profile_picture_url'] ?? '', 
            $isJoinable
        );
        
        // 4. Handle Response
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/alliance/profile/' . $allianceId);
    }
}