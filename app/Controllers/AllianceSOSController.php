<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\ViewContextService;
use App\Models\Services\AllianceSOSService;

class AllianceSOSController extends BaseController
{
    private AllianceSOSService $sosService;

    public function __construct(
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService,
        AllianceSOSService $sosService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->sosService = $sosService;
    }

    /**
     * Show the SOS Command Center.
     */
    public function showPage(): void
    {
        $userId = $this->session->get('user_id');
        
        if (!$this->sosService->canAccessSOS($userId)) {
            $this->session->setFlash('error', 'You must be in an alliance to access this channel.');
            $this->redirect('/dashboard');
            return;
        }

        // Calculate cooldown status
        $lastSos = $this->session->get('last_sos_time_' . $userId) ?? 0;
        $cooldown = 4 * 3600; // 4 hours
        $timeSince = time() - $lastSos;
        $remaining = max(0, $cooldown - $timeSince);

        $this->render('alliance/sos_manage.php', [
            'pageTitle' => 'Distress Signal Control',
            'layoutMode' => 'full', // Optional: Use full width if needed
            'cooldownRemaining' => $remaining
        ]);
    }

    /**
     * Handle the SOS Broadcast.
     */
    public function broadcast(): void
    {
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'type' => 'required|string',
            'message' => 'string'
        ]);

        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/sos/manage');
            return;
        }

        $userId = $this->session->get('user_id');
        $lastSos = $this->session->get('last_sos_time_' . $userId) ?? 0;

        $response = $this->sosService->broadcastSOS(
            $userId, 
            $data['type'], 
            $data['message'] ?? '', 
            $lastSos
        );

        if ($response->isSuccess()) {
            // Update Cooldown
            $this->session->set('last_sos_time_' . $userId, time());
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }

        $this->redirect('/alliance/sos/manage');
    }
}