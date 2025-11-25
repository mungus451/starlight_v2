<?php

namespace App\Models\Services;

use App\Models\Repositories\StatsRepository;
use App\Models\Services\LevelCalculatorService;

/**
 * Responsible for gathering "Global" data required by the main layout.
 * (e.g., XP Bar, Navbar Stats, Unread Notification counts).
 * 
 * This prevents the BaseController from being coupled to specific domain logic.
 */
class ViewContextService
{
    private StatsRepository $statsRepo;
    private LevelCalculatorService $levelCalculator;

    public function __construct(
        StatsRepository $statsRepo,
        LevelCalculatorService $levelCalculator
    ) {
        $this->statsRepo = $statsRepo;
        $this->levelCalculator = $levelCalculator;
    }

    /**
     * Retrieves global context data for a logged-in user.
     *
     * @param int $userId
     * @return array
     */
    public function getGlobalLayoutData(int $userId): array
    {
        $data = [];
        
        // 1. Fetch RPG Stats for XP Bar
        $stats = $this->statsRepo->findByUserId($userId);
        
        if ($stats) {
            $xpData = $this->levelCalculator->getLevelProgress($stats->experience, $stats->level);
            
            $data['global_xp_data'] = $xpData;
            $data['global_user_level'] = $stats->level;
        }

        // Future: Add unread notification count here if we move away from AJAX polling
        // $data['global_unread_count'] = ...

        return $data;
    }
}