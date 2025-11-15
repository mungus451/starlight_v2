<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Models\Services\ArmoryService;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStats;
use App\Models\Entities\UserStructure;

/**
 * Handles all complex game logic calculations for power, income, etc.
 * This service reads data and returns detailed breakdowns.
 */
class PowerCalculatorService
{
    private Config $config;
    private ArmoryService $armoryService;

    public function __construct()
    {
        // This service can be instantiated on its own
        $this->armoryService = new ArmoryService();
        $this->config = new Config();
    }

    /**
     * Calculates a user's total "all-in" Offense Power and its components.
     *
     * @param int $userId
     * @param UserResource $resources
     * @param UserStats $stats
     * @param UserStructure $structures
     * @return array A detailed breakdown of the calculation
     */
    public function calculateOffensePower(
        int $userId,
        UserResource $resources,
        UserStats $stats,
        UserStructure $structures
    ): array {
        $config = $this->config->get('game_balance.attack');
        $soldiers = $resources->soldiers;
        
        // 1. Base Power from Units
        $baseUnitPower = $soldiers * $config['power_per_soldier'];
        
        // 2. Bonus from Armory
        $armoryBonus = $this->armoryService->getAggregateBonus($userId, 'soldier', 'attack', $soldiers);
        
        // 3. Total Base Power
        $totalBasePower = $baseUnitPower + $armoryBonus;

        // 4. Bonus from Structures
        $structureBonusPercent = $structures->offense_upgrade_level * $config['power_per_offense_level'];
        $structureBonusValue = $totalBasePower * $structureBonusPercent;

        // 5. Bonus from Stats
        $statBonusPercent = $stats->strength_points * $config['power_per_strength_point'];
        $statBonusValue = $totalBasePower * $statBonusPercent;

        // 6. Final Total Power
        $totalPower = $totalBasePower * (1 + $structureBonusPercent + $statBonusPercent);

        return [
            'total' => (int)$totalPower,
            'base_unit_power' => (int)$baseUnitPower,
            'armory_bonus' => (int)$armoryBonus,
            'total_base_power' => (int)$totalBasePower,
            'structure_bonus_pct' => $structureBonusPercent,
            'stat_bonus_pct' => $statBonusPercent,
            'structure_level' => $structures->offense_upgrade_level,
            'stat_points' => $stats->strength_points,
            'unit_count' => $soldiers
        ];
    }

    /**
     * Calculates a user's total Defense Power and its components.
     *
     * @param int $userId
     * @param UserResource $resources
     * @param UserStats $stats
     * @param UserStructure $structures
     * @return array A detailed breakdown of the calculation
     */
    public function calculateDefensePower(
        int $userId,
        UserResource $resources,
        UserStats $stats,
        UserStructure $structures
    ): array {
        $config = $this->config->get('game_balance.attack');
        $guards = $resources->guards;
        
        // 1. Base Power from Units
        $baseUnitPower = $guards * $config['power_per_guard'];
        
        // 2. Bonus from Armory
        $armoryBonus = $this->armoryService->getAggregateBonus($userId, 'guard', 'defense', $guards);
        
        // 3. Total Base Power
        $totalBasePower = $baseUnitPower + $armoryBonus;

        // 4. Bonuses from Structures
        $fortBonusPct = $structures->fortification_level * $config['power_per_fortification_level'];
        $defBonusPct = $structures->defense_upgrade_level * $config['power_per_defense_level'];
        $structureBonusPercent = $fortBonusPct + $defBonusPct;
        
        // 5. Bonus from Stats
        $statBonusPercent = $stats->constitution_points * $config['power_per_constitution_point'];

        // 6. Final Total Power
        $totalPower = $totalBasePower * (1 + $structureBonusPercent + $statBonusPercent);

        return [
            'total' => (int)$totalPower,
            'base_unit_power' => (int)$baseUnitPower,
            'armory_bonus' => (int)$armoryBonus,
            'total_base_power' => (int)$totalBasePower,
            'structure_bonus_pct' => $structureBonusPercent,
            'stat_bonus_pct' => $statBonusPercent,
            'fort_level' => $structures->fortification_level,
            'def_level' => $structures->defense_upgrade_level,
            'stat_points' => $stats->constitution_points,
            'unit_count' => $guards
        ];
    }

    /**
     * Calculates a user's total Spy Offense Power and its components.
     *
     * @param int $userId
     * @param UserResource $resources
     * @param UserStructure $structures
     * @return array A detailed breakdown of the calculation
     */
    public function calculateSpyPower(
        int $userId,
        UserResource $resources,
        UserStructure $structures
    ): array {
        $config = $this->config->get('game_balance.spy');
        $spies = $resources->spies;
        
        // 1. Base Power from Units
        $baseUnitPower = $spies * ($config['base_power_per_spy'] ?? 1.0);
        
        // 2. Bonus from Armory
        $armoryBonus = $this->armoryService->getAggregateBonus($userId, 'spy', 'attack', $spies);
        
        // 3. Total Base Power
        $totalBasePower = $baseUnitPower + $armoryBonus;

        // 4. Bonus from Structures
        $structureBonusPercent = $structures->spy_upgrade_level * $config['offense_power_per_level'];

        // 5. Final Total Power
        $totalPower = $totalBasePower * (1 + $structureBonusPercent);

        return [
            'total' => (int)$totalPower,
            'base_unit_power' => (int)$baseUnitPower,
            'armory_bonus' => (int)$armoryBonus,
            'total_base_power' => (int)$totalBasePower,
            'structure_bonus_pct' => $structureBonusPercent,
            'structure_level' => $structures->spy_upgrade_level,
            'unit_count' => $spies
        ];
    }

    /**
     * Calculates a user's total Sentry Defense Power and its components.
     *
     * @param int $userId
     * @param UserResource $resources
     * @param UserStructure $structures
     * @return array A detailed breakdown of the calculation
     */
    public function calculateSentryPower(
        int $userId,
        UserResource $resources,
        UserStructure $structures
    ): array {
        $config = $this->config->get('game_balance.spy');
        $sentries = $resources->sentries;
        
        // 1. Base Power from Units
        $baseUnitPower = $sentries * ($config['base_power_per_sentry'] ?? 1.0);
        
        // 2. Bonus from Armory
        $armoryBonus = $this->armoryService->getAggregateBonus($userId, 'sentry', 'defense', $sentries);
        
        // 3. Total Base Power
        $totalBasePower = $baseUnitPower + $armoryBonus;

        // 4. Bonus from Structures
        $structureBonusPercent = $structures->spy_upgrade_level * $config['defense_power_per_level'];

        // 5. Final Total Power
        $totalPower = $totalBasePower * (1 + $structureBonusPercent);

        return [
            'total' => (int)$totalPower,
            'base_unit_power' => (int)$baseUnitPower,
            'armory_bonus' => (int)$armoryBonus,
            'total_base_power' => (int)$totalBasePower,
            'structure_bonus_pct' => $structureBonusPercent,
            'structure_level' => $structures->spy_upgrade_level,
            'unit_count' => $sentries
        ];
    }

    /**
     * --- MODIFIED METHOD ---
     * Calculates a user's total income per turn and its components,
     * including workers, stats, and armory bonuses.
     *
     * @param int $userId
     * @param UserResource $resources
     * @param UserStats $stats
     * @param UserStructure $structures
     * @return array A detailed breakdown of the calculation
     */
    public function calculateIncomePerTurn(
        int $userId,
        UserResource $resources,
        UserStats $stats,
        UserStructure $structures
    ): array {
        $config = $this->config->get('game_balance.turn_processor');
        
        // 1. Base Production
        $econIncome = $structures->economy_upgrade_level * $config['credit_income_per_econ_level'];
        $workerIncome = $resources->workers * $config['credit_income_per_worker'];
        $baseProduction = $econIncome + $workerIncome;

        // 2. Percentage Bonuses (from stats)
        $statBonusPct = $stats->wealth_points * $config['credit_bonus_per_wealth_point'];
        
        // 3. Flat Bonuses (from armory)
        // We assume 'credit_bonus' in armory_items.php is a FLAT value per worker
        $armoryBonus = $this->armoryService->getAggregateBonus($userId, 'worker', 'credit_bonus', $resources->workers);
        
        // 4. Total Credit Income (for Credits on Hand)
        // (Base Production * (1 + Stat %)) + Flat Armory Bonus
        $totalCreditIncome = (int)floor($baseProduction * (1 + $statBonusPct)) + $armoryBonus;
        
        // 5. Interest Income (for Banked Credits)
        $interestIncome = (int)floor($resources->banked_credits * $config['bank_interest_rate']);
        
        // 6. Citizen Income
        $citizenIncome = $structures->population_level * $config['citizen_growth_per_pop_level'];

        return [
            'total_credit_income' => $totalCreditIncome, // Econ + Workers + Stat% + Armory
            'interest' => $interestIncome,
            'total_citizens' => $citizenIncome,
            
            // Breakdown components
            'econ_income' => $econIncome,
            'worker_income' => $workerIncome,
            'base_production' => $baseProduction,
            'stat_bonus_pct' => $statBonusPct,
            'armory_bonus' => $armoryBonus,
            
            // Source data for view
            'econ_level' => $structures->economy_upgrade_level,
            'pop_level' => $structures->population_level,
            'worker_count' => $resources->workers,
            'wealth_points' => $stats->wealth_points,
            'banked_credits' => $resources->banked_credits,
            'interest_rate_pct' => $config['bank_interest_rate']
        ];
    }
}