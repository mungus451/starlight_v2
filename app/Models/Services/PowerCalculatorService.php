<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStats;
use App\Models\Entities\UserStructure;
use App\Models\Repositories\AllianceStructureRepository;
use App\Models\Repositories\AllianceStructureDefinitionRepository;

/**
 * Handles all complex game logic calculations for power, income, etc.
 * * Refactored for Strict Dependency Injection.
 */
class PowerCalculatorService
{
    private Config $config;
    private ArmoryService $armoryService;
    private AllianceStructureRepository $allianceStructRepo;
    private AllianceStructureDefinitionRepository $structDefRepo;
    
    /** @var array|null Cached structure definitions keyed by structure_key */
    private ?array $structureDefinitionsCache = null;

    /**
     * DI Constructor.
     *
     * @param Config $config
     * @param ArmoryService $armoryService
     * @param AllianceStructureRepository $allianceStructRepo
     * @param AllianceStructureDefinitionRepository $structDefRepo
     */
    public function __construct(
        Config $config,
        ArmoryService $armoryService,
        AllianceStructureRepository $allianceStructRepo,
        AllianceStructureDefinitionRepository $structDefRepo
    ) {
        $this->config = $config;
        $this->armoryService = $armoryService;
        $this->allianceStructRepo = $allianceStructRepo;
        $this->structDefRepo = $structDefRepo;
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
     * Calculates a user's total Spy Offense Power.
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
     * Calculates a user's total Sentry Defense Power.
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
     * Calculates a user's total income per turn.
     *
     * @param int $userId
     * @param UserResource $resources
     * @param UserStats $stats
     * @param UserStructure $structures
     * @param int|null $allianceId Optional alliance ID for bonus calculations
     * @return array
     */
    public function calculateIncomePerTurn(
        int $userId,
        UserResource $resources,
        UserStats $stats,
        UserStructure $structures,
        ?int $allianceId = null
    ): array {
        $config = $this->config->get('game_balance.turn_processor');
        
        // 1. Base Production
        $econIncome = $structures->economy_upgrade_level * $config['credit_income_per_econ_level'];
        $workerIncome = $resources->workers * $config['credit_income_per_worker'];
        $baseProduction = $econIncome + $workerIncome;

        // 2. Percentage Bonuses (from stats)
        $statBonusPct = $stats->wealth_points * $config['credit_bonus_per_wealth_point'];
        
        // 3. Flat Bonuses (from armory)
        $armoryBonus = $this->armoryService->getAggregateBonus($userId, 'worker', 'credit_bonus', $resources->workers);
        
        // 4. Total Credit Income (for Credits on Hand)
        $totalCreditIncome = (int)floor($baseProduction * (1 + $statBonusPct)) + $armoryBonus;
        
        // 5. Interest Income (for Banked Credits)
        $interestIncome = (int)floor($resources->banked_credits * $config['bank_interest_rate']);
        
        // 6. Citizen Income (base from personal structures)
        $baseCitizenIncome = $structures->population_level * $config['citizen_growth_per_pop_level'];
        
        // 7. Alliance Structure Bonuses for citizen growth
        $allianceCitizenBonus = 0;
        if ($allianceId !== null) {
            $allianceCitizenBonus = $this->calculateAllianceCitizenBonus($allianceId);
        }
        
        $totalCitizenIncome = $baseCitizenIncome + $allianceCitizenBonus;

        return [
            'total_credit_income' => $totalCreditIncome,
            'interest' => $interestIncome,
            'total_citizens' => $totalCitizenIncome,
            'econ_income' => $econIncome,
            'worker_income' => $workerIncome,
            'base_production' => $baseProduction,
            'stat_bonus_pct' => $statBonusPct,
            'armory_bonus' => $armoryBonus,
            'econ_level' => $structures->economy_upgrade_level,
            'pop_level' => $structures->population_level,
            'worker_count' => $resources->workers,
            'wealth_points' => $stats->wealth_points,
            'banked_credits' => $resources->banked_credits,
            'interest_rate_pct' => $config['bank_interest_rate'],
            'base_citizen_income' => $baseCitizenIncome,
            'alliance_citizen_bonus' => $allianceCitizenBonus
        ];
    }

    /**
     * Calculates the citizen growth bonus from alliance structures.
     *
     * @param int $allianceId
     * @return int The flat citizen bonus per turn
     */
    private function calculateAllianceCitizenBonus(int $allianceId): int
    {
        // Get all structures owned by this alliance
        $ownedStructures = $this->allianceStructRepo->findByAllianceId($allianceId);
        
        if (empty($ownedStructures)) {
            return 0;
        }
        
        // Get cached structure definitions (keyed by structure_key)
        $defByKey = $this->getStructureDefinitions();
        
        $citizenBonus = 0;
        $allBonusMultiplier = 0;
        
        // First pass: calculate base bonuses and find any all_bonus_multiplier
        foreach ($ownedStructures as $key => $structure) {
            if (!isset($defByKey[$key])) {
                continue;
            }
            
            $def = $defByKey[$key];
            $bonuses = $def->getBonuses();
            
            foreach ($bonuses as $bonus) {
                $bonusType = $bonus['type'] ?? '';
                $bonusValue = $bonus['value'] ?? 0;
                
                if ($bonusType === 'citizen_growth_flat') {
                    // Multiply by structure level for scaling
                    $citizenBonus += $bonusValue * $structure->level;
                } elseif ($bonusType === 'all_bonus_multiplier') {
                    // Accumulate all bonus multipliers (from Warlord's Throne, etc.)
                    $allBonusMultiplier += $bonusValue * $structure->level;
                }
            }
        }
        
        // Apply the all_bonus_multiplier to citizen bonus if present
        if ($allBonusMultiplier > 0) {
            $citizenBonus = (int)floor($citizenBonus * (1 + $allBonusMultiplier));
        }
        
        return $citizenBonus;
    }
    
    /**
     * Gets structure definitions with service-level caching.
     * Definitions are static data that rarely changes, so caching avoids
     * redundant database queries during batch processing.
     *
     * @return array Structure definitions keyed by structure_key
     */
    private function getStructureDefinitions(): array
    {
        if ($this->structureDefinitionsCache === null) {
            $definitions = $this->structDefRepo->getAllDefinitions();
            $this->structureDefinitionsCache = [];
            foreach ($definitions as $def) {
                $this->structureDefinitionsCache[$def->structure_key] = $def;
            }
        }
        
        return $this->structureDefinitionsCache;
    }
}