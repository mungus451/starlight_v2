<?php

namespace App\Models\Services;

use App\Core\Config;

/**
 * Pure logic service for calculating level requirements and progress percentages.
 * Refactored for Dependency Injection.
 */
class LevelCalculatorService
{
    private Config $config;

    /**
     * @param Config $config Injected configuration loader
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Calculates the total accumulated XP required to REACH a specific level.
     *
     * @param int $level The level to target
     * @return int
     */
    public function getXpRequiredForLevel(int $level): int
    {
        if ($level <= 1) {
            return 0;
        }

        // Fetch values from config using dot notation
        $xpConfig = $this->config->get('game_balance.xp', []);
        
        $base = $xpConfig['base_xp'] ?? 1000;
        $exponent = $xpConfig['exponent'] ?? 1.5;

        return (int)floor($base * pow($level - 1, $exponent));
    }

    /**
     * Calculates the percentage progress towards the NEXT level.
     *
     * @param int $currentXp The user's current total XP
     * @param int $currentLevel The user's current level
     * @return array Contains 'percent', 'current_xp', 'next_level_xp', 'xp_remaining'
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
     *
     * @param int $totalXp
     * @return int The calculated level
     */
    public function calculateLevelFromXp(int $totalXp): int
    {
        $xpConfig = $this->config->get('game_balance.xp', []);
        
        $base = $xpConfig['base_xp'] ?? 1000;
        $exponent = $xpConfig['exponent'] ?? 1.5;

        if ($totalXp <= 0) {
            return 1;
        }

        // Inverse formula: Level = (XP / Base)^(1/Exponent) + 1
        $rawLevel = pow($totalXp / $base, 1 / $exponent) + 1;
        
        return (int)floor($rawLevel);
    }
}