<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStats;
use App\Models\Entities\UserStructure;
use App\Models\Repositories\AllianceStructureRepository;
use App\Models\Repositories\AllianceStructureDefinitionRepository;
use App\Models\Repositories\EdictRepository;
use App\Models\Repositories\GeneralRepository;
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
    private GeneralRepository $generalRepo;
    private EffectService $effectService;
    private array $generalConfig;

    /** @var array|null Cached structure definitions keyed by structure_key */
    private ?array $structureDefinitionsCache = null;

    /** @var array Runtime cache for alliance bonuses to optimize loops */
    private array $allianceBonusCache = [];

    /** @var array Runtime cache for edict bonuses */
    private array $edictBonusCache = [];

    /** @var array Runtime cache for general bonuses */
    private array $generalBonusCache = [];

    /**
     * DI Constructor.
     */
    public function __construct(
        Config $config,
        ArmoryService $armoryService,
        AllianceStructureRepository $allianceStructRepo,
        AllianceStructureDefinitionRepository $structDefRepo,
        EdictRepository $edictRepo,
        GeneralRepository $generalRepo,
        EffectService $effectService
    ) {
        $this->config = $config;
        $this->armoryService = $armoryService;
        $this->allianceStructRepo = $allianceStructRepo;
        $this->structDefRepo = $structDefRepo;
        $this->edictRepo = $edictRepo;
        $this->generalRepo = $generalRepo;
        $this->effectService = $effectService;
        $this->generalConfig = $this->config->get('game_balance.generals', []);
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
    
    public function calculateGeneralBonuses(int $userId): array
    {
        if (isset($this->generalBonusCache[$userId])) {
            return $this->generalBonusCache[$userId];
        }

        $generals = $this->generalRepo->findByUserId($userId);
        $weaponsConfig = $this->config->get('elite_weapons', []);
        
        $bonuses = [
            'flat_offense' => 0,
            'flat_defense' => 0,
            'flat_shield' => 0,
            'offense_mult' => 1.0,
            'defense_mult' => 1.0,
        ];
        
        foreach ($generals as $gen) {
            $key = $gen['weapon_slot_1'] ?? null;
            if (!$key || !isset($weaponsConfig[$key])) continue;
            
            $mods = $weaponsConfig[$key]['modifiers'] ?? [];
            
            $bonuses['flat_offense'] += ($mods['flat_offense'] ?? 0);
            $bonuses['flat_defense'] += ($mods['flat_defense'] ?? 0);
            $bonuses['flat_shield'] += ($mods['flat_shield'] ?? 0);
            
            if (isset($mods['global_offense_mult'])) {
                $bonuses['offense_mult'] *= $mods['global_offense_mult'];
            }
            if (isset($mods['global_defense_mult'])) {
                $bonuses['defense_mult'] *= $mods['global_defense_mult'];
            }
        }
        
        $this->generalBonusCache[$userId] = $bonuses;
        return $bonuses;
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
        // Generals
        $genBonuses = $this->calculateGeneralBonuses($userId);

        // Apply Army Capacity (Soldiers only)
        $baseCapacity = $this->generalConfig['base_capacity'] ?? 500;
        $capacityPerGeneral = $this->generalConfig['capacity_per_general'] ?? 10000;
        $generalCount = $this->generalRepo->countByUserId($userId);
        $armyCapacity = $baseCapacity + ($generalCount * $capacityPerGeneral);

        $effectiveSoldiers = min($soldiers, $armyCapacity);
        $ineffectiveSoldiers = $soldiers - $effectiveSoldiers;

        // Calculate base power using ONLY effective soldiers
        $baseUnitPower = $effectiveSoldiers * $config['power_per_soldier'];
        $armoryBonus = $this->armoryService->getAggregateBonus($userId, 'soldier', 'attack', $effectiveSoldiers); // Armory bonus also only for effective soldiers

        // Add General's flat offense
        $baseUnitPower += $genBonuses['flat_offense'];
        
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

        // 4. Edict Bonuses
        $edictBonuses = $this->getEdictBonuses($userId);
        $edictBonusPercent = $edictBonuses['offense_power_percent'] ?? 0.0;

        // 5. Final Total Power
        $totalMultiplier = 1 + $structureBonusPercent + $statBonusPercent + $allianceBonusPercent + $edictBonusPercent;
        $totalMultiplier *= $genBonuses['offense_mult'];
        
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
            'general_flat' => $genBonuses['flat_offense'],
            'general_mult' => $genBonuses['offense_mult'],
            'void_buff_mult' => $voidBuffMultiplier,
            'structure_level' => $structures->offense_upgrade_level,
            'stat_points' => $stats->strength_points,
            'unit_count' => $soldiers,
            'army_capacity' => $armyCapacity,
            'effective_soldiers' => $effectiveSoldiers,
            'ineffective_soldiers' => $ineffectiveSoldiers
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
        
        // Generals
        $genBonuses = $this->calculateGeneralBonuses($userId);
        $baseUnitPower += $genBonuses['flat_defense'];
        
        $totalBasePower = $baseUnitPower + $armoryBonus;

        // 2. Personal Bonuses
        $fortBonusPct = $structures->fortification_level * $config['power_per_fortification_level'];
        $defBonusPct = $structures->defense_upgrade_level * $config['power_per_defense_level'];
        $structureBonusPercent = $fortBonusPct + $defBonusPct;
        
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

        // 5. Final Total Power
        $totalMultiplier = 1 + $structureBonusPercent + $statBonusPercent + $allianceBonusPercent + $edictBonusPercent;
        $totalMultiplier *= $genBonuses['defense_mult'];
        
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
            'general_flat' => $genBonuses['flat_defense'],
            'general_mult' => $genBonuses['defense_mult'],
            'void_debuff_mult' => $voidDebuffMultiplier,
            'fort_level' => $structures->fortification_level,
            'def_level' => $structures->defense_upgrade_level,
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
        
        // 1. Base Production
        $econIncome = $structures->economy_upgrade_level * $config['credit_income_per_econ_level'];
        $workerIncome = $resources->workers * $config['credit_income_per_worker'];
        $armoryBonus = $this->armoryService->getAggregateBonus($userId, 'worker', 'credit_bonus', $resources->workers);
        
        $baseProduction = $econIncome + $workerIncome + $armoryBonus;

        // 2. Personal Bonuses
        $statBonusPct = $stats->wealth_points * $config['credit_bonus_per_wealth_point'];
        
        $accBase = $config['accounting_firm_base_bonus'] ?? 0.01;
        $accMult = $config['accounting_firm_multiplier'] ?? 1.0;
        $accLevel = $structures->accounting_firm_level;
        
        $accountingFirmBonusPct = 0.0;
        if ($accLevel > 0) {
            $accountingFirmBonusPct = $accBase * $accLevel * pow($accMult, max(0, $accLevel - 1));
        }
        
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
        
        // 5. Total Credit Income
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
        
        $detailedBreakdown[] = [ 'label' => "Total Scalar", 'value' => (($finalIncomeScalar - 1) * 100) . "%", 'type' => 'scalar' ];


        // 6. Interest Income
        $rawInterest = (int)floor($resources->banked_credits * $config['bank_interest_rate']);
        $interestIncome = (int)floor($rawInterest * $edictInterestMultiplier);
        
        // 7. Citizen Income
        $baseCitizenIncome = $structures->population_level * $config['citizen_growth_per_pop_level'];
        $totalCitizenIncome = $baseCitizenIncome + $allianceCitizenFlat;
        $totalCitizenIncome = (int)floor($totalCitizenIncome * $edictCitizenMultiplier * $edictCitizenMultiplicative);

        // 8. Research Data
        $researchDataIncome = $structures->quantum_research_lab_level * ($config['research_data_per_lab_level'] ?? 0);

        // 9. Dark Matter (Affected by Edict Resource Multiplier)
        $dmLevel = $structures->dark_matter_siphon_level;
        $darkMatterIncome = 0;
        if ($dmLevel > 0) {
            $base = ($config['dark_matter_per_siphon_level'] ?? 0) * $dmLevel * pow($config['dark_matter_production_multiplier'] ?? 1.0, max(0, $dmLevel - 1));
            $darkMatterIncome = $base * $edictResourceMultiplier;
        }

        // 10. Naquadah (Affected by Edict Resource Multiplier)
        $nqLevel = $structures->naquadah_mining_complex_level;
        $naquadahIncome = 0;
        if ($nqLevel > 0) {
            $base = ($config['naquadah_per_mining_complex_level'] ?? 0) * $nqLevel * pow($config['naquadah_production_multiplier'] ?? 1.0, max(0, $nqLevel - 1));
            $naquadahIncome = $base * $edictResourceMultiplier;
        }

        // 11. Protoform (Affected by Edict Resource Multiplier)
        $protoformIncome = $structures->protoform_vat_level * ($config['protoform_per_vat_level'] ?? 0) * $edictResourceMultiplier;

        // --- VOID BUFF: Resource Boost (+25%) ---
        if ($this->effectService->hasActiveEffect($userId, 'void_resource_boost')) {
            $boost = 1.25;
            $totalCreditIncome = (int)floor($totalCreditIncome * $boost);
            // $interestIncome usually excluded from resource boosts? User said "Resource Generation". Interest is passive. I'll include it for now to be generous.
            $interestIncome = (int)floor($interestIncome * $boost); 
            $totalCitizenIncome = (int)floor($totalCitizenIncome * $boost);
            $researchDataIncome = (int)floor($researchDataIncome * $boost);
            $darkMatterIncome *= $boost;
            $naquadahIncome *= $boost;
            $protoformIncome *= $boost;
            
            $detailedBreakdown[] = [ 'label' => "Void Resource Buff", 'value' => "+25%", 'type' => 'scalar' ];
        }

        return [
            'total_credit_income' => $totalCreditIncome,
            'interest' => $interestIncome,
            'total_citizens' => $totalCitizenIncome,
            'research_data_income' => $researchDataIncome,
            'dark_matter_income' => $darkMatterIncome,
            'naquadah_income' => $naquadahIncome,
            'protoform_income' => $protoformIncome,
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
            'accounting_firm_level' => $structures->accounting_firm_level,
            'worker_count' => $resources->workers,
            'wealth_points' => $stats->wealth_points,
            'banked_credits' => $resources->banked_credits,
            'interest_rate_pct' => $config['bank_interest_rate'],
            'base_citizen_income' => $baseCitizenIncome,
            'alliance_citizen_bonus' => $allianceCitizenFlat,
            'quantum_research_lab_level' => $structures->quantum_research_lab_level,
            'dark_matter_siphon_level' => $structures->dark_matter_siphon_level,
            'naquadah_mining_complex_level' => $structures->naquadah_mining_complex_level,
            'protoform_vat_level' => $structures->protoform_vat_level
        ];
    }

    public function calculateSpyPower(int $userId, UserResource $resources, UserStructure $structures): array {
        $config = $this->config->get('game_balance.spy');
        $spies = $resources->spies;
        $base = $spies * ($config['base_power_per_spy'] ?? 1.0);
        $armory = $this->armoryService->getAggregateBonus($userId, 'spy', 'attack', $spies);
        $totalBase = $base + $armory;
        $structBonus = $structures->spy_upgrade_level * $config['offense_power_per_level'];

        // Edict Spy Bonus
        $edictBonuses = $this->getEdictBonuses($userId);
        $edictBonus = $edictBonuses['spy_success_percent'] ?? 0.0; // Assuming this maps to power for now

        $total = $totalBase * (1 + $structBonus + $edictBonus);
        
        return [
            'total' => (int)$total,
            'base_unit_power' => (int)$base,
            'armory_bonus' => (int)$armory,
            'total_base_power' => (int)$totalBase,
            'structure_bonus_pct' => $structBonus,
            'edict_bonus_pct' => $edictBonus,
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
        
        // Edict Sentry Bonus
        $edictBonuses = $this->getEdictBonuses($userId);
        $edictBonus = $edictBonuses['spy_defense_percent'] ?? 0.0;

        $total = $totalBase * (1 + $structBonus + $edictBonus);
        
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
            'structure_level' => $structures->spy_upgrade_level,
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
        
        // General Bonus
        $genBonuses = $this->calculateGeneralBonuses($userId);
        $flatShield = $genBonuses['flat_shield'];

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