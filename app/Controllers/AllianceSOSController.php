<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\ViewContextService;
use App\Models\Services\NotificationService;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRepository;

class AllianceSOSController extends BaseController
{
    private NotificationService $notificationService;
    private UserRepository $userRepo;
    private AllianceRepository $allianceRepo;

    public function __construct(
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService,
        NotificationService $notificationService,
        UserRepository $userRepo,
        AllianceRepository $allianceRepo
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->notificationService = $notificationService;
        $this->userRepo = $userRepo;
        $this->allianceRepo = $allianceRepo;
    }

    /**
     * Show the SOS Command Center.
     */
    public function showPage(): void
    {
        $userId = $this->session->get('user_id');
        $user = $this->userRepo->findById($userId);

        if (!$user || !$user->alliance_id) {
            $this->session->setFlash('error', 'You must be in an alliance to access this channel.');
            $this->redirect('/dashboard');
            return;
        }

        // Calculate cooldown status
        // For now, we store last SOS time in session or DB? 
        // Ideally DB (user_stats or new column), but session is easier for MVP.
        // Let's use Redis/Cache key if available, otherwise just rely on Session for per-user limit.
        // Or better, check the last notification sent by this user?
        // Let's assume session for this MVP step.
        
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
        $user = $this->userRepo->findById($userId);

        if (!$user || !$user->alliance_id) {
            $this->redirect('/dashboard');
            return;
        }

        // Cooldown Check
        $lastSos = $this->session->get('last_sos_time_' . $userId) ?? 0;
        $cooldown = 4 * 3600;
        if (time() - $lastSos < $cooldown) {
            $this->session->setFlash('error', 'Communication relays are cooling down. Please wait.');
            $this->redirect('/alliance/sos/manage');
            return;
        }

        // Construct Message
        $typeLabel = match($data['type']) {
            'invasion' => 'INVASION DEFENSE',
            'resource' => 'RESOURCE REQUEST',
            'strike'   => 'COORDINATED STRIKE',
            default    => 'GENERAL ALERT'
        };

        $customMsg = trim($data['message'] ?? '');
        $fullMessage = "<b>[{$typeLabel}]</b><br>Commander {$user->characterName} transmits: " . 
                       ($customMsg ? htmlspecialchars($customMsg) : "Immediate assistance required!");

        // Send Notification
        $this->notificationService->notifyAllianceMembers(
            $user->alliance_id,
            $userId, // Sender (optional logic in service to exclude sender? usually yes)
            'SOS DISTRESS SIGNAL',
            $fullMessage,
            '/alliance/profile/' . $user->alliance_id
        );

        // Update Cooldown
        $this->session->set('last_sos_time_' . $userId, time());

        $this->session->setFlash('success', 'Distress signal broadcasted to all channels!');
        $this->redirect('/alliance/sos/manage');
    }
}
