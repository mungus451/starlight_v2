<?php

namespace App\Models\Services;

use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Entities\User;

/**
 * Service to generate dynamic suggestions for the player.
 */
class AdvisorService
{
    private StatsRepository $statsRepo;

    public function __construct(
        StatsRepository $statsRepo
    ) {
        $this->statsRepo = $statsRepo;
    }

    /**
     * Gathers a list of actionable suggestions for the user.
     *
     * @param User $user
     * @return array
     */
    public function getSuggestions(User $user): array
    {
        $suggestions = [];
        $stats = $this->statsRepo->findByUserId($user->id);

        // Suggestion 1: Check for unspent level up points
        if ($stats && $stats->level_up_points > 0) {
            $suggestions[] = [
                'text' => "You have {$stats->level_up_points} unspent level-up points.",
                'link' => '/level-up',
                'icon' => 'fa-bolt'
            ];
        }
        
        // Add more suggestion logic here in the future...

        return $suggestions;
    }
}
