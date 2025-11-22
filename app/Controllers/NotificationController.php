<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Models\Services\NotificationService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles notification displays and AJAX polling.
 */
class NotificationController extends BaseController
{
    private NotificationService $notificationService;

    /**
     * DI Constructor.
     * * @param NotificationService $notificationService
     * @param Session $session
     * @param CSRFService $csrfService
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        NotificationService $notificationService,
        Session $session,
        CSRFService $csrfService,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $levelCalculator, $statsRepo);
        $this->notificationService = $notificationService;
    }

    /**
     * Displays the user's notification inbox.
     * Route: GET /notifications
     */
    public function index(): void
    {
        $userId = $this->session->get('user_id');
        
        // Mark all as read immediately upon visiting the inbox? 
        // Or let them click "Mark All Read"? 
        // We'll let them manually mark or click specific ones, but we fetch recent history here.
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
        header('Content-Type: application/json');

        $userId = $this->session->get('user_id');
        if (!$userId) {
            echo json_encode(['unread' => 0, 'latest' => null]);
            return;
        }

        $unreadCount = $this->notificationService->getUnreadCount($userId);
        $latest = null;

        // If there are unread items, fetch the very latest one to display in the Push Notification
        if ($unreadCount > 0) {
            $recent = $this->notificationService->getRecentNotifications($userId, 1);
            if (!empty($recent)) {
                $item = $recent[0];
                // Only send the latest if it is actually unread
                if (!$item->is_read) {
                    $latest = [
                        'title' => $item->title,
                        'message' => $item->message, // Truncated in JS if needed
                        'type' => $item->type,
                        'created_at' => $item->created_at
                    ];
                }
            }
        }

        echo json_encode([
            'unread' => $unreadCount,
            'latest' => $latest
        ]);
    }

    /**
     * AJAX Endpoint to mark a single notification as read.
     * Route: POST /notifications/read/{id}
     */
    public function handleMarkRead(array $vars): void
    {
        header('Content-Type: application/json');
        
        $userId = $this->session->get('user_id');
        $notifId = (int)($vars['id'] ?? 0);

        if ($this->notificationService->markAsRead($notifId, $userId)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update or unauthorized.']);
        }
    }

    /**
     * AJAX Endpoint to mark all notifications as read.
     * Route: POST /notifications/read-all
     */
    public function handleMarkAllRead(): void
    {
        header('Content-Type: application/json');

        // Basic CSRF Check for bulk actions
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            echo json_encode(['success' => false, 'error' => 'Invalid Token']);
            return;
        }

        $userId = $this->session->get('user_id');
        
        if ($this->notificationService->markAllRead($userId)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
    }
}