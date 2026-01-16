<?php

namespace App\Models\AI\Strategies;

use App\Models\Entities\User;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStats;
use App\Models\Entities\UserStructure;

interface NpcStrategyInterface
{
    /**
     * Executes the strategy for a given NPC.
     * 
     * @param User $npc
     * @param UserResource $resources
     * @param UserStats $stats
     * @param UserStructure $structures
     * @return array Report of actions taken.
     */
    public function execute(User $npc, UserResource $resources, UserStats $stats, UserStructure $structures): array;

    /**
     * Determines the current high-level state (Growth, War, Turtle, etc.)
     */
    public function determineState(UserResource $resources, UserStats $stats, UserStructure $structures): string;
}
