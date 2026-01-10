<?php

namespace App\Models\Services;

use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Services\NotificationService;
use App\Core\ServiceResponse;

/**
 * Handles Alliance Distress Signals (SOS).
 */
class AllianceSOSService
{
    private UserRepository $userRepo;
    private AllianceRepository $allianceRepo;
    private NotificationService $notificationService;

    public function __construct(
        UserRepository $userRepo,
        AllianceRepository $allianceRepo,
        NotificationService $notificationService
    ) {
        $this->userRepo = $userRepo;
        $this->allianceRepo = $allianceRepo;
        $this->notificationService = $notificationService;
    }

    /**
     * Broadcasts an SOS signal to alliance members.
     * 
     * @param int $userId
     * @param string $type
     * @param string $message
     * @param int $lastSosTime Timestamp of last SOS sent by user (from Session/DB)
     * @return ServiceResponse
     */
    public function broadcastSOS(int $userId, string $type, string $message, int $lastSosTime): ServiceResponse
    {
        // 1. User Validation
        $user = $this->userRepo->findById($userId);
        if (!$user || !$user->alliance_id) {
            return ServiceResponse::error("You must be in an alliance to broadcast an SOS.");
        }

        // 2. Cooldown Check
        $cooldown = 4 * 3600; // 4 hours
        if (time() - $lastSosTime < $cooldown) {
            $remaining = ceil(($cooldown - (time() - $lastSosTime)) / 60);
            return ServiceResponse::error("Communication relays are cooling down. Wait {$remaining} minutes.");
        }

        // 3. Construct Message (Plain Text for Service Purity)
        $typeLabel = match($type) {
            'invasion' => 'INVASION DEFENSE',
            'resource' => 'RESOURCE REQUEST',
            'strike'   => 'COORDINATED STRIKE',
            default    => 'GENERAL ALERT'
        };

        $customMsg = trim($message);
        // Use plain text formatting. View layer or notification renderer handles HTML if needed.
        $fullMessage = "[{$typeLabel}] Commander {$user->characterName} transmits: " . 
                       ($customMsg ? $customMsg : "Immediate assistance required!");

        // 4. Send Notification
        $this->notificationService->notifyAllianceMembers(
            $user->alliance_id,
            $userId, 
            'SOS DISTRESS SIGNAL',
            $fullMessage,
            '/alliance/profile/' . $user->alliance_id
        );

        return ServiceResponse::success("Distress signal broadcasted to all channels!");
    }

    /**
     * Checks if a user is eligible to view the SOS page.
     * @param int $userId
     * @return bool
     */
    public function canAccessSOS(int $userId): bool
    {
        $user = $this->userRepo->findById($userId);
        return $user && $user->alliance_id;
    }
}
