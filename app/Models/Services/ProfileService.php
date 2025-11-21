<?php

namespace App\Models\Services;

use App\Models\Repositories\UserRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceRoleRepository;

/**
 * Handles all business logic for fetching public player profiles.
 * * Refactored for Strict Dependency Injection.
 */
class ProfileService
{
    private UserRepository $userRepo;
    private StatsRepository $statsRepo;
    private AllianceRepository $allianceRepo;
    private AllianceRoleRepository $roleRepo;

    /**
     * DI Constructor.
     *
     * @param UserRepository $userRepo
     * @param StatsRepository $statsRepo
     * @param AllianceRepository $allianceRepo
     * @param AllianceRoleRepository $roleRepo
     */
    public function __construct(
        UserRepository $userRepo,
        StatsRepository $statsRepo,
        AllianceRepository $allianceRepo,
        AllianceRoleRepository $roleRepo
    ) {
        $this->userRepo = $userRepo;
        $this->statsRepo = $statsRepo;
        $this->allianceRepo = $allianceRepo;
        $this->roleRepo = $roleRepo;
    }

    /**
     * Gets all data needed to render a public profile page.
     * This method curates a "safe" array, never exposing
     * sensitive User entity data (email, phone, etc.).
     *
     * @param int $targetUserId The ID of the user to look up
     * @param int $viewerUserId The ID of the user viewing the page
     * @return array|null A data array or null if target not found
     */
    public function getProfileData(int $targetUserId, int $viewerUserId): ?array
    {
        // 1. Get Target User's Data
        $targetUser = $this->userRepo->findById($targetUserId);
        if (!$targetUser) {
            return null; // User not found
        }
        
        $targetStats = $this->statsRepo->findByUserId($targetUserId);
        
        $targetAlliance = null;
        if ($targetUser->alliance_id) {
            $targetAlliance = $this->allianceRepo->findById($targetUser->alliance_id);
        }

        // 2. Get Viewer's Data to check permissions
        $viewerUser = $this->userRepo->findById($viewerUserId);
        $viewerRole = null;
        if ($viewerUser && $viewerUser->alliance_id) {
            $viewerRole = $this->roleRepo->findById($viewerUser->alliance_role_id);
        }

        // 3. Determine if "Invite" button should show
        $canInvite = false;
        if (
            $viewerRole &&                             // Viewer is in an alliance and has a role
            $viewerRole->can_invite_members &&         // Viewer has "invite" permission
            $targetUser->alliance_id === null &&       // Target is not in an alliance
            $viewerUser->alliance_id !== null &&       // Viewer is in an alliance
            $targetUser->id !== $viewerUser->id        // Cannot invite self
        ) {
            $canInvite = true;
        }

        // 4. Build the safe, public-facing data array
        $publicData = [
            'profile' => [
                'id' => $targetUser->id,
                'character_name' => $targetUser->characterName,
                'bio' => $targetUser->bio,
                'profile_picture_url' => $targetUser->profile_picture_url,
                'created_at' => $targetUser->createdAt
            ],
            'stats' => $targetStats, // UserStats entity is safe to pass
            'alliance' => $targetAlliance, // Alliance entity is safe
            'viewer' => [
                'alliance_id' => $viewerUser->alliance_id ?? null,
                'can_invite' => $canInvite
            ]
        ];

        return $publicData;
    }
}