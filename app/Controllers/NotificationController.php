<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Core\Config;
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
    private Config $config;

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
        ViewContextService $viewContextService,
        Config $config
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->notificationService = $notificationService;
        $this->config = $config;
    }

    /**
     * Displays the user's notification inbox.
     * Route: GET /notifications
     */
    public function index(): void
    {
        $userId = $this->session->get('user_id');

        // Get page number from query string, default to 1
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 20; // Items per page

        $data = $this->notificationService->getPaginatedNotifications($userId, $page, $perPage);

        $spaNotificationsEnabled = (bool)$this->config->get('spa.notifications_enabled', false);

        $this->render('notifications/index.php', [
            'title' => 'Command Uplink',
            'notifications' => $data['notifications'],
            'pagination' => $data['pagination'],
            'spa_notifications_enabled' => $spaNotificationsEnabled,
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

    /**
     * API Endpoint to get user notification preferences.
     * Route: GET /notifications/preferences
     */
    public function getPreferences(): void
    {
        $userId = $this->session->get('user_id');

        if (!$userId) {
            $this->jsonResponse(['error' => 'Not authenticated'], 401);
            return;
        }

        $preferences = $this->notificationService->getPreferences($userId);

        $this->jsonResponse([
            'attack_enabled' => $preferences->attack_enabled,
            'spy_enabled' => $preferences->spy_enabled,
            'alliance_enabled' => $preferences->alliance_enabled,
            'system_enabled' => $preferences->system_enabled,
            'push_notifications_enabled' => $preferences->push_notifications_enabled
        ]);
    }

    public function apiList(): void
    {
        $userId = (int)$this->session->get('user_id', 0);
        if ($userId <= 0) {
            $this->jsonResponse(['error' => 'Not authenticated'], 401);
            return;
        }

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? max(1, min(50, (int)$_GET['per_page'])) : 20;
        $data = $this->notificationService->getPaginatedNotifications($userId, $page, $perPage);

        $notifications = array_map(function ($notif): array {
            return [
                'id' => (int)$notif->id,
                'type' => (string)$notif->type,
                'title' => (string)$notif->title,
                'message' => (string)$notif->message,
                'link' => $notif->link !== null ? (string)$notif->link : null,
                'is_read' => (bool)$notif->is_read,
                'created_at' => (string)$notif->created_at,
            ];
        }, $data['notifications']);

        $this->jsonResponse([
            'notifications' => $notifications,
            'pagination' => $data['pagination'],
        ]);
    }

    public function apiUnread(): void
    {
        $userId = (int)$this->session->get('user_id', 0);
        if ($userId <= 0) {
            $this->jsonResponse(['error' => 'Not authenticated'], 401);
            return;
        }

        $data = $this->notificationService->getPollingData($userId);
        $this->jsonResponse($data);
    }

    public function apiMarkRead(array $vars): void
    {
        $userId = (int)$this->session->get('user_id', 0);
        if ($userId <= 0) {
            $this->jsonResponse(['error' => 'Not authenticated'], 401);
            return;
        }

        if (!$this->isValidApiCsrf()) {
            $this->jsonResponse(['error' => 'Invalid CSRF token'], 422);
            return;
        }

        $notificationId = (int)($vars['id'] ?? 0);
        $response = $this->notificationService->markAsRead($notificationId, $userId);

        if (!$response->isSuccess()) {
            $this->jsonResponse(['error' => $response->message], 400);
            return;
        }

        $this->jsonResponse(['success' => true]);
    }

    public function apiMarkAllRead(): void
    {
        $userId = (int)$this->session->get('user_id', 0);
        if ($userId <= 0) {
            $this->jsonResponse(['error' => 'Not authenticated'], 401);
            return;
        }

        if (!$this->isValidApiCsrf()) {
            $this->jsonResponse(['error' => 'Invalid CSRF token'], 422);
            return;
        }

        $response = $this->notificationService->markAllRead($userId);
        if (!$response->isSuccess()) {
            $this->jsonResponse(['error' => $response->message], 400);
            return;
        }

        $this->jsonResponse(['success' => true]);
    }

    public function apiGetPreferences(): void
    {
        $this->getPreferences();
    }

    public function apiUpdatePreferences(): void
    {
        $userId = (int)$this->session->get('user_id', 0);
        if ($userId <= 0) {
            $this->jsonResponse(['error' => 'Not authenticated'], 401);
            return;
        }

        if (!$this->isValidApiCsrf()) {
            $this->jsonResponse(['error' => 'Invalid CSRF token'], 422);
            return;
        }

        $attackEnabled = $this->toBool($_POST['attack_enabled'] ?? false);
        $spyEnabled = $this->toBool($_POST['spy_enabled'] ?? false);
        $allianceEnabled = $this->toBool($_POST['alliance_enabled'] ?? false);
        $systemEnabled = $this->toBool($_POST['system_enabled'] ?? false);
        $pushEnabled = $this->toBool($_POST['push_notifications_enabled'] ?? false);

        $response = $this->notificationService->updatePreferences(
            $userId,
            $attackEnabled,
            $spyEnabled,
            $allianceEnabled,
            $systemEnabled,
            $pushEnabled
        );

        if (!$response->isSuccess()) {
            $this->jsonResponse(['error' => $response->message], 400);
            return;
        }

        $this->jsonResponse(['success' => true]);
    }

    private function isValidApiCsrf(): bool
    {
        $headerToken = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $postToken = (string)($_POST['csrf_token'] ?? '');
        $token = $headerToken !== '' ? $headerToken : $postToken;

        return $token !== '' && $this->csrfService->validateToken($token);
    }

    private function toBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL);
    }
}
