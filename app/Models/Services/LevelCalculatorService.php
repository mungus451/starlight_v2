<?php

namespace App\Models\Services;

use App\Core\Config;

/**
 * Pure logic service for calculating level requirements and progress percentages.
 * Does not interact with the database.
 */
class LevelCalculatorService
{
    private array $xpConfig;

    public function __construct()
    {
        $config = new Config();
        $this->xpConfig = $config->get('game_balance.xp', []);
    }

    /**
     * Calculates the total accumulated XP required to REACH a specific level.
     * Formula: Base * ((TargetLevel - 1) ^ Exponent)
     * * Example (Base 1000, Exp 1.5):
     * Level 1: 0 XP
     * Level 2: 1000 * (1^1.5) = 1000 XP
     * Level 3: 1000 * (2^1.5) = 2828 XP
     *
     * @param int $level The level to target (e.g., 2 to see how much XP needed to leave lvl 1)
     * @return int
     */
    public function getXpRequiredForLevel(int $level): int
    {
        if ($level <= 1) {
            return 0;
        }

        $base = $this->xpConfig['base_xp'] ?? 1000;
        $exponent = $this->xpConfig['exponent'] ?? 1.5;

        // We subtract 1 because level 1 is the "start" (0 distance)
        return (int)floor($base * pow($level - 1, $exponent));
    }

    /**
     * Calculates the percentage progress towards the NEXT level.
     * * @param int $currentXp The user's current total XP
     * @param int $currentLevel The user's current level
     * @return array Contains 'percent' (float 0-100), 'current_level_xp_start', 'next_level_xp_req'
     */
    public function getLevelProgress(int $currentXp, int $currentLevel): array
    {
        $startXp = $this->getXpRequiredForLevel($currentLevel);
        $nextLevelXp = $this->getXpRequiredForLevel($currentLevel + 1);
        
        $xpInLevel = $currentXp - $startXp;
        $xpNeededForLevel = $nextLevelXp - $startXp;
        
        $percent = 0;
        if ($xpNeededForLevel > 0) {
            $percent = ($xpInLevel / $xpNeededForLevel) * 100;
        }
        
        // Clamp between 0 and 100 for UI safety
        $percent = max(0, min(100, $percent));

        return [
            'percent' => $percent,
            'current_xp' => $currentXp,
            'next_level_xp' => $nextLevelXp,
            'xp_remaining' => $nextLevelXp - $currentXp
        ];
    }

    /**
     * Determines the new level based on total XP.
     * Used when a user gains enough XP to potentially level up multiple times.
     *
     * @param int $totalXp
     * @return int The level corresponding to this XP amount
     */
    public function calculateLevelFromXp(int $totalXp): int
    {
        // Inverting the formula: Level = (XP / Base)^(1/Exponent) + 1
        $base = $this->xpConfig['base_xp'] ?? 1000;
        $exponent = $this->xpConfig['exponent'] ?? 1.5;

        if ($totalXp <= 0) {
            return 1;
        }

        // Calculate raw level
        $rawLevel = pow($totalXp / $base, 1 / $exponent) + 1;
        
        // Floor it to get the integer level
        return (int)floor($rawLevel);
    }
}