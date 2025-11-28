<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\NotificationService;
use App\Models\Services\ViewContextService;

/**
 * Handles notification displays and AJAX polling.
 * * Refactored to delegate polling logic to Service.
 * * Refactored Phase 4: Uses BaseController::jsonResponse.
 */
class NotificationController extends BaseController
{
    private NotificationService $notificationService;

    /**
     * DI Constructor.
     * 
     * @param NotificationService $notificationService
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param ViewContextService $viewContextService
     */
    public function __construct(
        NotificationService $notificationService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->notificationService = $notificationService;
    }

    /**
     * Displays the user's notification inbox.
     * Route: GET /notifications
     */
    public function index(): void
    {
        $userId = $this->session->get('user_id');
        
        $notifications = $this->notificationService->getRecentNotifications($userId, 50);

        $this->render('notifications/index.php', [
            'title' => 'Command Uplink',
            'notifications' => $notifications
        ]);
    }

    /**
     * API Endpoint for the AJAX Poller.
     * Returns the current unread count and the latest alert for Push Notifications.
     * Route: GET /notifications/check
     */
    public function check(): void
    {
        $userId = $this->session->get('user_id');
        
        if (!$userId) {
            $this->jsonResponse(['unread' => 0, 'latest' => null]);
            return;
        }

        // Delegate logic to service
        $data = $this->notificationService->getPollingData($userId);

        $this->jsonResponse($data);
    }

    /**
     * AJAX Endpoint to mark a single notification as read.
     * Route: POST /notifications/read/{id}
     */
    public function handleMarkRead(array $vars): void
    {
        $userId = $this->session->get('user_id');
        $notifId = (int)($vars['id'] ?? 0);

        $response = $this->notificationService->markAsRead($notifId, $userId);
        
        if ($response->isSuccess()) {
            $this->jsonResponse(['success' => true]);
        } else {
            $this->jsonResponse(['success' => false, 'error' => $response->message]);
        }
    }

    /**
     * AJAX Endpoint to mark all notifications as read.
     * Route: POST /notifications/read-all
     */
    public function handleMarkAllRead(): void
    {
        // 1. Manual Validation (to return JSON instead of Redirect)
        $val = $this->validator->make($_POST, [
            'csrf_token' => 'required'
        ]);

        if ($val->fails()) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid input data']);
            return;
        }
        
        $data = $val->validated();

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid Token']);
            return;
        }

        $userId = $this->session->get('user_id');
        
        $response = $this->notificationService->markAllRead($userId);
        
        if ($response->isSuccess()) {
            $this->jsonResponse(['success' => true]);
        } else {
            $this->jsonResponse(['success' => false, 'error' => $response->message]);
        }
    }
}