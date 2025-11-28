<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\AllianceManagementService;
use App\Models\Services\ViewContextService;

/**
 * Handles alliance settings and profile configuration.
 * * Refactored to consume ServiceResponse objects.
 * * Updated Phase 16: File Upload support.
 * * FIX: Added null check for alliance_id to prevent Type Errors.
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
     * @param ViewContextService $viewContextService
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
            'is_joinable' => 'nullable', // Checkbox
            'remove_picture' => 'nullable' // Checkbox
        ]);

        // 2. Validate Session State (CRITICAL FIX)
        $allianceId = $this->session->get('alliance_id');
        if (empty($allianceId)) {
            $this->session->setFlash('error', 'Session error: You are not currently in an alliance.');
            $this->redirect('/dashboard');
            return;
        }
        $allianceId = (int)$allianceId;

        // 3. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }

        $adminId = $this->session->get('user_id');
        
        // Convert checkbox state to boolean
        $isJoinable = isset($data['is_joinable']) && $data['is_joinable'] == '1';
        $removePhoto = isset($data['remove_picture']) && $data['remove_picture'] == '1';
        
        $file = $_FILES['profile_picture'] ?? ['error' => UPLOAD_ERR_NO_FILE];

        // 4. Execute Service
        $response = $this->mgmtService->updateProfile(
            $adminId, 
            $allianceId, 
            $data['description'] ?? '', 
            $file,
            $removePhoto,
            $isJoinable
        );
        
        // 5. Handle Response
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/alliance/profile/' . $allianceId);
    }
}