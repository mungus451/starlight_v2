<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;
use App\Models\Services\NotificationService;

/**
 * Handles displaying and managing user notifications.
 */
class NotificationController extends BaseController
{
    /**
     * Strict DI Constructor.
     * Note: We must inject NotificationService here AND pass it to the parent.
     */
    public function __construct(
        Session $session,
        CSRFService $csrfService,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo,
        NotificationService $notificationService
    ) {
        parent::__construct($session, $csrfService, $levelCalculator, $statsRepo, $notificationService);
    }

    /**
     * Displays the notifications page.
     */
    public function show(array $vars): void
    {
        $userId = $this->session->get('user_id');
        $page = isset($vars['page']) ? (int)$vars['page'] : 1;
        
        $data = $this->notificationService->getUserNotifications($userId, $page, 20);
        
        $this->render('notifications/show.php', [
            'title' => 'Notifications',
            'notifications' => $data['notifications'],
            'pagination' => $data['pagination'],
            'layoutMode' => 'full'
        ]);
    }

    /**
     * Handles marking a single notification as read via POST.
     */
    public function handleMarkRead(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/notifications');
            return;
        }

        $notificationId = (int)($_POST['notification_id'] ?? 0);
        $userId = $this->session->get('user_id');

        if ($notificationId > 0) {
            $this->notificationService->markAsRead($userId, $notificationId);
            $this->session->setFlash('success', 'Notification marked as read.');
        }

        $this->redirect('/notifications');
    }

    /**
     * Handles marking ALL notifications as read.
     */
    public function handleMarkAllRead(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/notifications');
            return;
        }

        $userId = $this->session->get('user_id');
        $this->notificationService->markAllRead($userId);
        
        $this->session->setFlash('success', 'All notifications marked as read.');
        $this->redirect('/notifications');
    }
}