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
 * * Refactored Phase 2: Fully wired Alliance Structure Bonuses.
 * * Added clearCache() for testing.
 */
class PowerCalculatorService
{
    private Config $config;
    private ArmoryService $armoryService;
    private AllianceStructureRepository $allianceStructRepo;
    private AllianceStructureDefinitionRepository $structDefRepo;
    
    /** @var array|null Cached structure definitions keyed by structure_key */
    private ?array $structureDefinitionsCache = null;

    /** @var array Runtime cache for alliance bonuses to optimize loops */
    private array $allianceBonusCache = [];

    /**
     * DI Constructor.
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
     * Clears internal runtime caches.
     * Essential for unit testing state changes within a single script execution.
     */
    public function clearCache(): void
    {
        $this->allianceBonusCache = [];
        $this->structureDefinitionsCache = null;
    }

    /**
     * Calculates a user's total "all-in" Offense Power and its components.
     */
    public function calculateOffensePower(
        int $userId,
        UserResource $resources,
        UserStats $stats,
        UserStructure $structures,
        ?int $allianceId = null
    ): array {
        $config = $this->config->get('game_balance.attack');
        $soldiers = $resources->soldiers;
        
        // 1. Base Power
        $baseUnitPower = $soldiers * $config['power_per_soldier'];
        $armoryBonus = $this->armoryService->getAggregateBonus($userId, 'soldier', 'attack', $soldiers);
        $totalBasePower = $baseUnitPower + $armoryBonus;

        // 2. Personal Bonuses
        $structureBonusPercent = $structures->offense_upgrade_level * $config['power_per_offense_level'];
        $statBonusPercent = $stats->strength_points * $config['power_per_strength_point'];

        // 3. Alliance Bonuses
        $allianceBonusPercent = 0.0;
        if ($allianceId) {
            $allianceBonuses = $this->getAllianceBonuses($allianceId);
            $allianceBonusPercent = $allianceBonuses['offense_bonus_percent'];
        }

        // 4. Final Total Power
        $totalMultiplier = 1 + $structureBonusPercent + $statBonusPercent + $allianceBonusPercent;
        $totalPower = $totalBasePower * $totalMultiplier;

        return [
            'total' => (int)$totalPower,
            'base_unit_power' => (int)$baseUnitPower,
            'armory_bonus' => (int)$armoryBonus,
            'total_base_power' => (int)$totalBasePower,
            'structure_bonus_pct' => $structureBonusPercent,
            'stat_bonus_pct' => $statBonusPercent,
            'alliance_bonus_pct' => $allianceBonusPercent,
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
        UserStructure $structures,
        ?int $allianceId = null
    ): array {
        $config = $this->config->get('game_balance.attack');
        $guards = $resources->guards;
        
        // 1. Base Power
        $baseUnitPower = $guards * $config['power_per_guard'];
        $armoryBonus = $this->armoryService->getAggregateBonus($userId, 'guard', 'defense', $guards);
        $totalBasePower = $baseUnitPower + $armoryBonus;

        // 2. Personal Bonuses
        $fortBonusPct = $structures->fortification_level * $config['power_per_fortification_level'];
        $defBonusPct = $structures->defense_upgrade_level * $config['power_per_defense_level'];
        $structureBonusPercent = $fortBonusPct + $defBonusPct;
        
        $statBonusPct = $stats->constitution_points * $config['power_per_constitution_point'];

        // 3. Alliance Bonuses
        $allianceBonusPercent = 0.0;
        if ($allianceId) {
            $allianceBonuses = $this->getAllianceBonuses($allianceId);
            $allianceBonusPercent = $allianceBonuses['defense_bonus_percent'];
        }

        // 4. Final Total Power
        $totalMultiplier = 1 + $structureBonusPercent + $statBonusPercent + $allianceBonusPercent;
        $totalPower = $totalBasePower * $totalMultiplier;

        return [
            'total' => (int)$totalPower,
            'base_unit_power' => (int)$baseUnitPower,
            'armory_bonus' => (int)$armoryBonus,
            'total_base_power' => (int)$totalBasePower,
            'structure_bonus_pct' => $structureBonusPercent,
            'stat_bonus_pct' => $statBonusPercent,
            'alliance_bonus_pct' => $allianceBonusPercent,
            'fort_level' => $structures->fortification_level,
            'def_level' => $structures->defense_upgrade_level,
            'stat_points' => $stats->constitution_points,
            'unit_count' => $guards
        ];
    }

    /**
     * Calculates a user's total income per turn.
     * Integrated with Alliance Bonuses (Credits & Citizens).
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

        // 2. Personal Bonuses
        $statBonusPct = $stats->wealth_points * $config['credit_bonus_per_wealth_point'];
        $armoryBonus = $this->armoryService->getAggregateBonus($userId, 'worker', 'credit_bonus', $resources->workers);
        $accountingFirmBonusPct = $structures->accounting_firm_level * 0.01; // 1% per level
        
        // 3. Alliance Bonuses (Credits)
        $allianceCreditMultiplier = 0.0;
        $allianceCitizenFlat = 0;
        
        if ($allianceId !== null) {
            $allyBonuses = $this->getAllianceBonuses($allianceId);
            
            // "resource_bonus_percent" (Hub) and "income_bonus_percent" (Nexus) both boost credits
            $allianceCreditMultiplier = $allyBonuses['resource_bonus_percent'] + $allyBonuses['income_bonus_percent'];
            $allianceCitizenFlat = $allyBonuses['citizen_growth_flat'];
        }
        
        // 4. Total Credit Income
        // Multipliers are additive: 1 + Stat% + Alliance% + AccountingFirm%
        $totalMultiplier = 1 + $statBonusPct + $allianceCreditMultiplier + $accountingFirmBonusPct;
        $totalCreditIncome = (int)floor($baseProduction * $totalMultiplier) + $armoryBonus;
        
        // 5. Interest Income
        $interestIncome = (int)floor($resources->banked_credits * $config['bank_interest_rate']);
        
        // 6. Citizen Income
        $baseCitizenIncome = $structures->population_level * $config['citizen_growth_per_pop_level'];
        $totalCitizenIncome = $baseCitizenIncome + $allianceCitizenFlat;

        return [
            'total_credit_income' => $totalCreditIncome,
            'interest' => $interestIncome,
            'total_citizens' => $totalCitizenIncome,
            'econ_income' => $econIncome,
            'worker_income' => $workerIncome,
            'base_production' => $baseProduction,
            'stat_bonus_pct' => $statBonusPct,
            'armory_bonus' => $armoryBonus,
            'alliance_credit_bonus_pct' => $allianceCreditMultiplier,
            'accounting_firm_bonus_pct' => $accountingFirmBonusPct,
            'econ_level' => $structures->economy_upgrade_level,
            'pop_level' => $structures->population_level,
            'accounting_firm_level' => $structures->accounting_firm_level,
            'worker_count' => $resources->workers,
            'wealth_points' => $stats->wealth_points,
            'banked_credits' => $resources->banked_credits,
            'interest_rate_pct' => $config['bank_interest_rate'],
            'base_citizen_income' => $baseCitizenIncome,
            'alliance_citizen_bonus' => $allianceCitizenFlat
        ];
    }

    public function calculateSpyPower(int $userId, UserResource $resources, UserStructure $structures): array {
        $config = $this->config->get('game_balance.spy');
        $spies = $resources->spies;
        $base = $spies * ($config['base_power_per_spy'] ?? 1.0);
        $armory = $this->armoryService->getAggregateBonus($userId, 'spy', 'attack', $spies);
        $totalBase = $base + $armory;
        $structBonus = $structures->spy_upgrade_level * $config['offense_power_per_level'];
        $total = $totalBase * (1 + $structBonus);
        
        return [
            'total' => (int)$total,
            'base_unit_power' => (int)$base,
            'armory_bonus' => (int)$armory,
            'total_base_power' => (int)$totalBase,
            'structure_bonus_pct' => $structBonus,
            'structure_level' => $structures->spy_upgrade_level,
            'unit_count' => $spies
        ];
    }

    public function calculateSentryPower(int $userId, UserResource $resources, UserStructure $structures): array {
        $config = $this->config->get('game_balance.spy');
        $sentries = $resources->sentries;
        $base = $sentries * ($config['base_power_per_sentry'] ?? 1.0);
        $armory = $this->armoryService->getAggregateBonus($userId, 'sentry', 'defense', $sentries);
        $totalBase = $base + $armory;
        $structBonus = $structures->spy_upgrade_level * $config['defense_power_per_level'];
        $total = $totalBase * (1 + $structBonus);
        
        return [
            'total' => (int)$total,
            'base_unit_power' => (int)$base,
            'armory_bonus' => (int)$armory,
            'total_base_power' => (int)$totalBase,
            'structure_bonus_pct' => $structBonus,
            'structure_level' => $structures->spy_upgrade_level,
            'unit_count' => $sentries
        ];
    }

    /**
     * Calculates consolidated bonuses for an alliance based on its structures.
     * Applies the 'all_bonus_multiplier' (Warlord's Throne) logic.
     * Uses runtime caching to optimize batch processing.
     *
     * @param int $allianceId
     * @return array
     */
    private function getAllianceBonuses(int $allianceId): array
    {
        if (isset($this->allianceBonusCache[$allianceId])) {
            return $this->allianceBonusCache[$allianceId];
        }

        $bonuses = [
            'offense_bonus_percent' => 0.0,
            'defense_bonus_percent' => 0.0,
            'income_bonus_percent' => 0.0,
            'resource_bonus_percent' => 0.0,
            'citizen_growth_flat' => 0
        ];

        $ownedStructures = $this->allianceStructRepo->findByAllianceId($allianceId);
        if (empty($ownedStructures)) {
            $this->allianceBonusCache[$allianceId] = $bonuses;
            return $bonuses;
        }

        $defByKey = $this->getStructureDefinitions();
        $throneMultiplier = 0.0;

        // Pass 1: Calculate Throne Multiplier
        if (isset($ownedStructures['warlords_throne']) && isset($defByKey['warlords_throne'])) {
            $level = $ownedStructures['warlords_throne']->level;
            $defJson = $defByKey['warlords_throne']->getBonuses();
            // Assuming the first bonus is the multiplier
            if (!empty($defJson[0]['value'])) {
                $throneMultiplier = $defJson[0]['value'] * $level;
            }
        }

        // Pass 2: Calculate other bonuses
        foreach ($ownedStructures as $key => $structure) {
            if (!isset($defByKey[$key]) || $key === 'warlords_throne') continue;

            $def = $defByKey[$key];
            $rawBonuses = $def->getBonuses();
            $level = $structure->level;

            foreach ($rawBonuses as $bonus) {
                $type = $bonus['type'] ?? '';
                $val = $bonus['value'] ?? 0;
                $totalVal = $val * $level;

                // Apply Throne Multiplier
                // Example: 10% base * (1 + 0.15 throne) = 11.5%
                $boostedVal = $totalVal * (1 + $throneMultiplier);

                // Add to aggregate array
                if (isset($bonuses[$type])) {
                    if ($type === 'citizen_growth_flat') {
                        $bonuses[$type] += (int)floor($boostedVal);
                    } else {
                        $bonuses[$type] += $boostedVal;
                    }
                }
            }
        }

        $this->allianceBonusCache[$allianceId] = $bonuses;
        return $bonuses;
    }
    
    /**
     * Gets structure definitions with service-level caching.
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