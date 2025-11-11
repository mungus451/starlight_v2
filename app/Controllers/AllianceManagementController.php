<?php

namespace App\Controllers;

use App\Models\Services\AllianceManagementService;

/**
 * Handles all "write" POST requests for alliance management.
 */
class AllianceManagementController extends BaseController
{
    private AllianceManagementService $mgmtService;

    public function __construct()
    {
        parent::__construct();
        $this->mgmtService = new AllianceManagementService();
    }

    /**
     * Handles a user's request to APPLY to an alliance.
     */
    public function handleApply(array $vars): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/list'); // Redirect to list on bad token
            return;
        }

        $userId = $this->session->get('user_id');
        $allianceId = (int)($vars['id'] ?? 0);

        $this->mgmtService->applyToAlliance($userId, $allianceId);
        
        // Redirect back to the profile page of the alliance they applied to
        $this->redirect('/alliance/profile/' . $allianceId);
    }

    /**
     * Handles a user's request to CANCEL their own application.
     */
    public function handleCancelApp(array $vars): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this.session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/list');
            return;
        }

        $userId = $this->session->get('user_id');
        $appId = (int)($vars['id'] ?? 0);

        // We need the alliance ID to redirect back
        $app = (new \App\Models\Repositories\ApplicationRepository(
            \App\Core\Database::getInstance()
        ))->findById($appId);
        $allianceId = $app ? $app->alliance_id : 'list';

        $this->mgmtService->cancelApplication($userId, $appId);
        
        $this->redirect('/alliance/profile/' . $allianceId);
    }

    /**
     * Handles a user's request to LEAVE their current alliance.
     */
    public function handleLeave(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this.session->setFlash('error', 'Invalid security token.');
            $this->redirect('/dashboard');
            return;
        }

        $userId = $this->session->get('user_id');
        $user = (new \App\Models\Repositories\UserRepository(
            \App\Core\Database::getInstance()
        ))->findById($userId);
        
        $allianceId = $user->alliance_id ?? 'list';

        $this->mgmtService->leaveAlliance($userId);
        
        // Redirect to the profile they just left
        $this->redirect('/alliance/profile/' . $allianceId);
    }

    /**
     * Handles a leader's request to ACCEPT an application.
     */
    public function handleAcceptApp(array $vars): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this.session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/list');
            return;
        }

        $adminId = $this->session->get('user_id');
        $appId = (int)($vars['id'] ?? 0);
        
        // We need the alliance ID to redirect back
        $app = (new \App\Models\Repositories\ApplicationRepository(
            \App\Core\Database::getInstance()
        ))->findById($appId);
        $allianceId = $app ? $app->alliance_id : 'list';

        $this->mgmtService->acceptApplication($adminId, $appId);
        
        $this->redirect('/alliance/profile/' . $allianceId);
    }

    /**
     * Handles a leader's request to REJECT an application.
     */
    public function handleRejectApp(array $vars): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this.session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/list');
            return;
        }

        $adminId = $this->session->get('user_id');
        $appId = (int)($vars['id'] ?? 0);
        
        // We need the alliance ID to redirect back
        $app = (new \App\Models\Repositories\ApplicationRepository(
            \App\Core\Database::getInstance()
        ))->findById($appId);
        $allianceId = $app ? $app->alliance_id : 'list';

        $this->mgmtService->rejectApplication($adminId, $appId);
        
        $this.redirect('/alliance/profile/' . $allianceId);
    }
}