<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStats;
use App\Models\Entities\UserStructure;
use App\Models\Repositories\AllianceStructureRepository;
use App\Models\Repositories\AllianceStructureDefinitionRepository;
use App\Models\Repositories\EdictRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Services\EffectService;

/**
 * Handles all complex game logic calculations for power, income, etc.
 * * Refactored Phase 3: Added Edict Modifiers.
 * * Phase 4: Added General Modifiers.
 * * Phase 5: Added Effect Modifiers (High Risk Protocol).
 */
class PowerCalculatorService
{
    private Config $config;
    private ArmoryService $armoryService;
    private AllianceStructureRepository $allianceStructRepo;
    private AllianceStructureDefinitionRepository $structDefRepo;
    private EdictRepository $edictRepo;
    private EffectService $effectService;
    private UserRepository $userRepo;

    /** @var array|null Cached structure definitions keyed by structure_key */
    private ?array $structureDefinitionsCache = null;

    /** @var array Runtime cache for alliance bonuses to optimize loops */
    private array $allianceBonusCache = [];

    /** @var array Runtime cache for edict bonuses */
    private array $edictBonusCache = [];

    /**
     * DI Constructor.
     */
    public function __construct(
        Config $config,
        ArmoryService $armoryService,
        AllianceStructureRepository $allianceStructRepo,
        AllianceStructureDefinitionRepository $structDefRepo,
        EdictRepository $edictRepo,
        EffectService $effectService,
        UserRepository $userRepo
    ) {
        $this->config = $config;
        $this->armoryService = $armoryService;
        $this->allianceStructRepo = $allianceStructRepo;
        $this->structDefRepo = $structDefRepo;
        $this->edictRepo = $edictRepo;
        $this->effectService = $effectService;
        $this->userRepo = $userRepo;
    }

    public function getIdentityBonuses(int $userId): array
    {
        $user = $this->userRepo->findById($userId);
        if (!$user) {
            return [
                'offense_mult' => 1.0,
                'defense_mult' => 1.0,
                'economic_mult' => 1.0,
                'spy_mult' => 1.0
            ];
        }

        $bonuses = [
            'offense_mult' => 1.0,
            'defense_mult' => 1.0,
            'economic_mult' => 1.0,
            'spy_mult' => 1.0
        ];

        // Race Bonuses
        switch ($user->race) {
            case 'Humans':
                $bonuses['offense_mult'] += 0.05;
                break;
            case 'Cyborgs':
                $bonuses['defense_mult'] += 0.05;
                break;
            case 'Sythera':
                $bonuses['economic_mult'] += 0.05;
                break;
            case 'Juggalo':
                $bonuses['spy_mult'] += 0.05;
                break;
        }

        // Class Bonuses
        switch ($user->class) {
            case 'Soldier':
                $bonuses['offense_mult'] += 0.05;
                break;
            case 'Guard':
                $bonuses['defense_mult'] += 0.05;
                break;
            case 'Thief':
                $bonuses['economic_mult'] += 0.05;
                break;
            case 'Cleric':
                $bonuses['spy_mult'] += 0.05;
                break;
        }

        return $bonuses;
    }

    /**
     * Clears internal runtime caches.
     */
    public function clearCache(): void
    {
        $this->allianceBonusCache = [];
        $this->edictBonusCache = [];
        $this->generalBonusCache = [];
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
        $armoryBonus = $this->armoryService->getAggregateBonus($userId, 'soldier', 'attack', $soldiers); // Armory bonus also only for effective soldiers
        
        $totalBasePower = $baseUnitPower + $armoryBonus;

        // 2. Personal Bonuses
        $structureBonusPercent = 0; // REVISIT: No direct offense structures remain
        $statBonusPercent = $stats->strength_points * $config['power_per_strength_point'];

        // 3. Alliance Bonuses
        $allianceBonusPercent = 0.0;
        if ($allianceId) {
            $allianceBonuses = $this->getAllianceBonuses($allianceId);
            $allianceBonusPercent = $allianceBonuses['offense_bonus_percent'];
        }

        // 4. Edict Bonuses
        $edictBonuses = $this->getEdictBonuses($userId);
        $edictBonusPercent = $edictBonuses['offense_power_percent'] ?? 0.0;

        // 5. Identity Bonuses (Race/Class)
        $identityBonuses = $this->getIdentityBonuses($userId);
        $identityOffenseMult = $identityBonuses['offense_mult'];

        // 6. Final Total Power
        $totalMultiplier = 1 + $structureBonusPercent + $statBonusPercent + $allianceBonusPercent + $edictBonusPercent;
        $totalMultiplier *= $identityOffenseMult;
        
        // Void Buff Check
        $voidBuffMultiplier = 1.0;
        if ($this->effectService->hasActiveEffect($userId, 'void_offense_boost')) {
            $voidBuffMultiplier = 1.10; // +10%
            $totalMultiplier *= $voidBuffMultiplier;
        }

        $totalPower = $totalBasePower * $totalMultiplier;

        return [
            'total' => (int)$totalPower,
            'base_unit_power' => (int)$baseUnitPower,
            'armory_bonus' => (int)$armoryBonus,
            'total_base_power' => (int)$totalBasePower,
            'structure_bonus_pct' => $structureBonusPercent,
            'stat_bonus_pct' => $statBonusPercent,
            'alliance_bonus_pct' => $allianceBonusPercent,
            'edict_bonus_pct' => $edictBonusPercent,
            'identity_offense_mult' => $identityOffenseMult,
            'void_buff_mult' => $voidBuffMultiplier,
            'stat_points' => $stats->strength_points,
            'unit_count' => $soldiers,
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
        $structureBonusPercent = 0; // REVISIT: No direct defense structures remain
        
        $statBonusPercent = $stats->constitution_points * $config['power_per_constitution_point'];

        // 3. Alliance Bonuses
        $allianceBonusPercent = 0.0;
        if ($allianceId) {
            $allianceBonuses = $this->getAllianceBonuses($allianceId);
            $allianceBonusPercent = $allianceBonuses['defense_bonus_percent'];
        }

        // 4. Edict Bonuses
        $edictBonuses = $this->getEdictBonuses($userId);
        $edictBonusPercent = $edictBonuses['defense_power_percent'] ?? 0.0;

        // 5. Identity Bonuses (Race/Class)
        $identityBonuses = $this->getIdentityBonuses($userId);
        $identityDefenseMult = $identityBonuses['defense_mult'];

        // 6. Final Total Power
        $totalMultiplier = 1 + $structureBonusPercent + $statBonusPercent + $allianceBonusPercent + $edictBonusPercent;
        $totalMultiplier *= $identityDefenseMult;
        
        // Void Debuff Check
        $voidDebuffMultiplier = 1.0;
        if ($this->effectService->hasActiveEffect($userId, 'void_defense_penalty')) {
            $voidDebuffMultiplier = 0.70; // -30%
            $totalMultiplier *= $voidDebuffMultiplier;
        }

        $totalPower = $totalBasePower * $totalMultiplier;

        return [
            'total' => (int)$totalPower,
            'base_unit_power' => (int)$baseUnitPower,
            'armory_bonus' => (int)$armoryBonus,
            'total_base_power' => (int)$totalBasePower,
            'structure_bonus_pct' => $structureBonusPercent,
            'stat_bonus_pct' => $statBonusPercent,
            'alliance_bonus_pct' => $allianceBonusPercent,
            'edict_bonus_pct' => $edictBonusPercent,
            'identity_defense_mult' => $identityDefenseMult,
            'void_debuff_mult' => $voidDebuffMultiplier,
            'stat_points' => $stats->constitution_points,
            'unit_count' => $guards
        ];
    }

    /**
     * Calculates a user's total income per turn.
     * Integrated with Alliance Bonuses and Edicts.
     */
    public function calculateIncomePerTurn(
        int $userId,
        UserResource $resources,
        UserStats $stats,
        UserStructure $structures,
        ?int $allianceId = null
    ): array {
        $config = $this->config->get('game_balance.turn_processor');
        $detailedBreakdown = [];
        
        // 1. Base Production
        $econIncome = $structures->economy_upgrade_level * $config['credit_income_per_econ_level'];
        $workerIncome = $resources->workers * $config['credit_income_per_worker'];
        $armoryBonus = $this->armoryService->getAggregateBonus($userId, 'worker', 'credit_bonus', $resources->workers);
        
        $baseProduction = $econIncome + $workerIncome + $armoryBonus;

        // 2. Personal Bonuses
        $statBonusPct = $stats->wealth_points * $config['credit_bonus_per_wealth_point'];
        
        $accountingFirmBonusPct = 0.0;
        
        
        // 3. Alliance Bonuses
        $allianceCreditMultiplier = 0.0;
        $allianceCitizenFlat = 0;
        
        if ($allianceId !== null) {
            $allyBonuses = $this->getAllianceBonuses($allianceId);
            $allianceCreditMultiplier = $allyBonuses['resource_bonus_percent'] + $allyBonuses['income_bonus_percent'];
            $allianceCitizenFlat = $allyBonuses['citizen_growth_flat'];
        }

        // 4. Edict Bonuses
        $edictBonuses = $this->getEdictBonuses($userId);
        $edictCreditMultiplier = $edictBonuses['credit_income_percent'] ?? 0.0;
        $edictTotalIncomeMultiplier = $edictBonuses['total_income_percent'] ?? 0.0;
        $edictCitizenMultiplier = 1.0 + ($edictBonuses['citizen_growth_percent'] ?? 0.0);
        $edictCitizenMultiplicative = $edictBonuses['citizen_growth_mult'] ?? 1.0;
        $edictResourceMultiplier = 1.0 + ($edictBonuses['resource_production_percent'] ?? 0.0);
        $edictInterestMultiplier = $edictBonuses['bank_interest_mult'] ?? 1.0;
        
        // 5. Identity Bonuses (Race/Class)
        $identityBonuses = $this->getIdentityBonuses($userId);
        $identityEconomicMult = $identityBonuses['economic_mult'];

        // 6. Total Credit Income
        // Multipliers are additive: 1 + Stat% + Alliance% + Accounting% + Edict%
        // But Edict Total Income% might be multiplicative at the end? Let's keep it additive for now to avoid confusion, or apply it to the final sum.
        // "Scorched Earth: -10% Total Income" suggests a final multiplier.
        // Let's treat "total_income_percent" as a final scalar.
        
        $additiveMultiplier = 1 + $statBonusPct + $allianceCreditMultiplier + $accountingFirmBonusPct + $edictCreditMultiplier;
        
        // Calculate raw amounts
        $amountFromWealth = (int)floor($baseProduction * $statBonusPct);
        $amountFromAccounting = (int)floor($baseProduction * $accountingFirmBonusPct);
        $amountFromAlliance = (int)floor($baseProduction * $allianceCreditMultiplier);
        $amountFromEdicts = (int)floor($baseProduction * $edictCreditMultiplier);
        
        $totalCreditIncome = (int)floor($baseProduction * $additiveMultiplier);
        
        // Apply "Total Income" scalar (e.g. Scorched Earth)
        $finalIncomeScalar = 1.0 + $edictTotalIncomeMultiplier;
        $finalIncomeScalar *= $identityEconomicMult; // Apply Identity Bonus here as a multiplier

        // High Risk Protocol Bonus (+50%)
        if ($this->effectService->hasActiveEffect($userId, 'high_risk_protocol')) {
            $finalIncomeScalar += 0.5;
            $detailedBreakdown[] = [ 'label' => "High Risk Protocol", 'value' => "+50%", 'type' => 'scalar' ];
        }

        // Radiation Sickness Debuff (-20%)
        if ($this->effectService->hasActiveEffect($userId, 'radiation_sickness')) {
            $finalIncomeScalar -= 0.2;
            $detailedBreakdown[] = [ 'label' => "Radiation Sickness", 'value' => "-20%", 'type' => 'scalar' ];
        }

        $totalCreditIncome = (int)floor($totalCreditIncome * $finalIncomeScalar);
        
        $detailedBreakdown[] = [ 'label' => "Identity Bonus", 'value' => (($identityEconomicMult - 1) * 100) . "%", 'type' => 'scalar' ];
        $detailedBreakdown[] = [ 'label' => "Total Scalar", 'value' => (($finalIncomeScalar - 1) * 100) . "%", 'type' => 'scalar' ];


        // 6. Interest Income (REMOVED)
        $interestIncome = 0;
        
        // 7. Citizen Income
        $baseCitizenIncome = $structures->population_level * $config['citizen_growth_per_pop_level'];
        $totalCitizenIncome = $baseCitizenIncome + $allianceCitizenFlat;
        $totalCitizenIncome = (int)floor($totalCitizenIncome * $edictCitizenMultiplier * $edictCitizenMultiplicative);

        // 8. Research Data
        $researchDataIncome = 0;

        // --- NEW: FUSION PLANT BONUS ---
        // Multiplies all "collector" outputs.
        // Applies to: Credits (Base), Research, DM, Naquadah.


        // --- VOID BUFF: Resource Boost (+25%) ---
        if ($this->effectService->hasActiveEffect($userId, 'void_resource_boost')) {
            $boost = 1.25;
            $totalCreditIncome = (int)floor($totalCreditIncome * $boost);
            // $interestIncome usually excluded from resource boosts? User said "Resource Generation". Interest is passive. I'll include it for now to be generous.
            $interestIncome = (int)floor($interestIncome * $boost); 
            $totalCitizenIncome = (int)floor($totalCitizenIncome * $boost);
            $researchDataIncome = (int)floor($researchDataIncome * $boost);
            
            $detailedBreakdown[] = [ 'label' => "Void Resource Buff", 'value' => "+25%", 'type' => 'scalar' ];
        }

        return [
            'total_credit_income' => $totalCreditIncome,
            'interest' => 0,
            'total_citizens' => $totalCitizenIncome,
            'research_data_income' => $researchDataIncome,
            'econ_income' => $econIncome,
            'worker_income' => $workerIncome,
            'base_production' => $baseProduction,
            'stat_bonus_pct' => $statBonusPct,
            'armory_bonus' => $armoryBonus,
            'alliance_credit_bonus_pct' => $allianceCreditMultiplier,
            'accounting_firm_bonus_pct' => $accountingFirmBonusPct,
            'edict_credit_bonus_pct' => $edictCreditMultiplier,
            'detailed_breakdown' => $detailedBreakdown,
            'econ_level' => $structures->economy_upgrade_level,
            'pop_level' => $structures->population_level,
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
        $structBonus = 0; // REVISIT: No direct spy structures remain

        // Edict Spy Bonus
        $edictBonuses = $this->getEdictBonuses($userId);
        $edictBonus = $edictBonuses['spy_success_percent'] ?? 0.0; // Assuming this maps to power for now

        // Identity Bonuses (Race/Class)
        $identityBonuses = $this->getIdentityBonuses($userId);
        $identitySpyMult = $identityBonuses['spy_mult'];

        $total = $totalBase * (1 + $structBonus + $edictBonus) * $identitySpyMult;
        
        return [
            'total' => (int)$total,
            'base_unit_power' => (int)$base,
            'armory_bonus' => (int)$armory,
            'total_base_power' => (int)$totalBase,
            'structure_bonus_pct' => $structBonus,
            'edict_bonus_pct' => $edictBonus,
            'identity_spy_mult' => $identitySpyMult,
            'unit_count' => $spies
        ];
    }

    public function calculateSentryPower(int $userId, UserResource $resources, UserStructure $structures): array {
        $config = $this->config->get('game_balance.spy');
        $sentries = $resources->sentries;
        $base = $sentries * ($config['base_power_per_sentry'] ?? 1.0);
        $armory = $this->armoryService->getAggregateBonus($userId, 'sentry', 'defense', $sentries);
        $totalBase = $base + $armory;
        $structBonus = 0; // REVISIT: No direct sentry structures remain
        
        // --- NEW: Neural Uplink Bonus ---
        $neuralLevel = $structures->neural_uplink_level ?? 0;
        if ($neuralLevel > 0) {
            $neuralBonus = $neuralLevel * ($config['neural_uplink_bonus_per_level'] ?? 0.02);
            $structBonus += $neuralBonus;
        }
        
        // Edict Sentry Bonus
        $edictBonuses = $this->getEdictBonuses($userId);
        $edictBonus = $edictBonuses['spy_defense_percent'] ?? 0.0;

        // Identity Bonuses (Race/Class)
        $identityBonuses = $this->getIdentityBonuses($userId);
        $identitySpyMult = $identityBonuses['spy_mult'];

        $total = $totalBase * (1 + $structBonus + $edictBonus) * $identitySpyMult;
        
        // Quantum Scrambler (+50%)
        if ($this->effectService->hasActiveEffect($userId, 'quantum_scrambler')) {
            $total = (int)ceil($total * 1.5);
        }

        return [
            'total' => (int)$total,
            'base_unit_power' => (int)$base,
            'armory_bonus' => (int)$armory,
            'total_base_power' => (int)$totalBase,
            'structure_bonus_pct' => $structBonus,
            'edict_bonus_pct' => $edictBonus,
            'identity_spy_mult' => $identitySpyMult,
            'unit_count' => $sentries
        ];
    }
    
    /**
     * Calculates consolidated bonuses for an alliance based on its structures.
     */
    private function getAllianceBonuses(int $allianceId): array
    {
        // ... (Existing code) ...
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

        if (isset($ownedStructures['warlords_throne']) && isset($defByKey['warlords_throne'])) {
            $level = $ownedStructures['warlords_throne']->level;
            $defJson = $defByKey['warlords_throne']->getBonuses();
            if (!empty($defJson[0]['value'])) {
                $throneMultiplier = $defJson[0]['value'] * $level;
            }
        }

        foreach ($ownedStructures as $key => $structure) {
            if (!isset($defByKey[$key]) || $key === 'warlords_throne') continue;

            $def = $defByKey[$key];
            $rawBonuses = $def->getBonuses();
            $level = $structure->level;

            foreach ($rawBonuses as $bonus) {
                $type = $bonus['type'] ?? '';
                $val = $bonus['value'] ?? 0;
                $totalVal = $val * $level;

                $boostedVal = $totalVal * (1 + $throneMultiplier);

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
     * Aggregates active edict bonuses for a user.
     */
    public function getEdictBonuses(int $userId): array
    {
        if (isset($this->edictBonusCache[$userId])) {
            return $this->edictBonusCache[$userId];
        }

        $activeEdicts = $this->edictRepo->findActiveByUserId($userId);
        $bonuses = [];

        foreach ($activeEdicts as $userEdict) {
            $definition = $this->edictRepo->getDefinition($userEdict->edict_key);
            if (!$definition) continue;

            foreach ($definition->modifiers as $key => $value) {
                // Determine if additive or multiplicative based on key naming convention or hardcoded logic
                // Most are additive.
                // 'bank_interest_mult' and 'citizen_growth_mult' are special.
                
                if (str_ends_with($key, '_mult')) {
                    // Multiplicative: Start at 1.0 if not set, then multiply
                    if (!isset($bonuses[$key])) {
                        $bonuses[$key] = 1.0;
                    }
                    $bonuses[$key] *= $value;
                } else {
                    // Additive
                    if (!isset($bonuses[$key])) {
                        $bonuses[$key] = 0.0;
                    }
                    $bonuses[$key] += $value;
                }
            }
        }

        $this->edictBonusCache[$userId] = $bonuses;
        return $bonuses;
    }

    // ... (rest of the file) ...

    public function calculateShieldPower(UserStructure $structures): array
    {
        $config = $this->config->get('game_balance.attack');
        $hpPerLevel = $config['shield_hp_per_level'] ?? 0;
        
        $userId = $structures->user_id;
        $edictBonuses = $this->getEdictBonuses($userId);
        $edictBonusPct = $edictBonuses['shield_hp_percent'] ?? 0.0;
        
        $flatShield = 0;

        $totalHp = ($structures->planetary_shield_level * $hpPerLevel);
        $totalHp += $flatShield; 
        
        $totalHp = $totalHp * (1 + $edictBonusPct);

        return [
            'total_shield_hp' => (int)$totalHp,
            'level' => $structures->planetary_shield_level,
            'hp_per_level' => $hpPerLevel,
            'flat_bonus' => $flatShield,
            'edict_bonus_pct' => $edictBonusPct
        ];
    }
    
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