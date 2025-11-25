<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\AllianceManagementService;
use App\Models\Services\ViewContextService; // --- NEW DEPENDENCY ---

/**
 * Handles alliance recruitment (applications and invites).
 * * Refactored to consume ServiceResponse objects.
 * * Fixed: Updated parent constructor call to use ViewContextService.
 */
class AllianceApplicationController extends BaseController
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
     * Handles a user's request to APPLY to an alliance.
     */
    public function handleApply(array $vars): void
    {
        // 1. Validate Input (CSRF only, ID is in route)
        $data = $this->validate($_POST, [
            'csrf_token' => 'required'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/list');
            return;
        }

        $userId = $this->session->get('user_id');
        $allianceId = (int)($vars['id'] ?? 0);

        // 3. Execute Service
        $response = $this->mgmtService->applyToAlliance($userId, $allianceId);
        
        // 4. Handle Response
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
            
            // Check if instant join occurred (Open Recruitment)
            if (isset($response->data['new_alliance_id'])) {
                $this->session->set('alliance_id', $response->data['new_alliance_id']);
                $this->redirect('/alliance/profile/' . $response->data['new_alliance_id']);
                return;
            }
            
            // Standard Application
            $this->redirect('/alliance/profile/' . $allianceId);
        } else {
            $this->session->setFlash('error', $response->message);
            $this->redirect('/alliance/list');
        }
    }

    /**
     * Handles a user's request to CANCEL their own application.
     */
    public function handleCancelApp(array $vars): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/list');
            return;
        }

        $userId = $this->session->get('user_id');
        $appId = (int)($vars['id'] ?? 0);

        $response = $this->mgmtService->cancelApplication($userId, $appId);
        
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/alliance/list');
    }

    /**
     * Handles a leader's request to ACCEPT an application.
     */
    public function handleAcceptApp(array $vars): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required'
        ]);

        $allianceId = $this->session->get('alliance_id');

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }

        $adminId = $this->session->get('user_id');
        $appId = (int)($vars['id'] ?? 0);
        
        $response = $this->mgmtService->acceptApplication($adminId, $appId);
        
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/alliance/profile/' . $allianceId);
    }

    /**
     * Handles a leader's request to REJECT an application.
     */
    public function handleRejectApp(array $vars): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required'
        ]);

        $allianceId = $this->session->get('alliance_id');

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }

        $adminId = $this->session->get('user_id');
        $appId = (int)($vars['id'] ?? 0);
        
        $response = $this->mgmtService->rejectApplication($adminId, $appId);
        
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/alliance/profile/' . $allianceId);
    }
    
    /**
     * Handles a member's request to INVITE a user from their profile page.
     */
    public function handleInvite(array $vars): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required'
        ]);

        $targetUserId = (int)($vars['id'] ?? 0);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/profile/' . $targetUserId);
            return;
        }

        $inviterId = $this->session->get('user_id');
        
        $response = $this->mgmtService->inviteUser($inviterId, $targetUserId);
        
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/profile/' . $targetUserId);
    }
}