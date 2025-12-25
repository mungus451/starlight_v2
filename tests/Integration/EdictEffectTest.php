<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Models\Services\PowerCalculatorService;
use App\Models\Repositories\EdictRepository;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStats;
use App\Models\Entities\UserStructure;
use App\Models\Entities\EdictDefinition;
use App\Models\Entities\UserEdict;
use App\Core\Config;
use App\Models\Repositories\AllianceStructureRepository;
use App\Models\Repositories\AllianceStructureDefinitionRepository;
use App\Models\Repositories\GeneralRepository;
use App\Models\Services\ArmoryService;

class EdictEffectTest extends TestCase
{
    private $edictRepo;
    private $powerCalculator;

    protected function setUp(): void
    {
        // Mock the repository layer
        $this->edictRepo = $this->createMock(EdictRepository::class);
        
        // Use real dependencies where possible, or mock them if they are complex
        $config = new Config(__DIR__ . '/../../config');
        $armoryService = $this->createMock(ArmoryService::class);
        $allianceStructRepo = $this->createMock(AllianceStructureRepository::class);
        $structDefRepo = $this->createMock(AllianceStructureDefinitionRepository::class);
        $generalRepo = $this->createMock(GeneralRepository::class);

        $this->powerCalculator = new PowerCalculatorService(
            $config,
            $armoryService,
            $allianceStructRepo,
            $structDefRepo,
            $this->edictRepo,
            $generalRepo
        );
    }

    public function testSyntheticIntegrationEdictProductionBoosts()
    {
        $userId = 1;
        $edictKey = 'synthetic_integration';

        // 1. Define the edict and its modifier
        $edictDefinition = new EdictDefinition(
            $edictKey,
            'Synthetic Integration',
            '', '', 'economic',
            ['resource_production_percent' => 0.20], // +20%
            0, 'credits'
        );
        $this->edictRepo->method('getDefinition')->with($edictKey)->willReturn($edictDefinition);

        // 2. Create mock user data (with all 15 UserResource args and non-zero structures for production)
        $userResources = new UserResource($userId, 1000, 1000, 1000, 1000.0, 1000, 1000, 1000, 1000, 1000, 1000, 0, 0, 0, 0.0);
        $userStats = new UserStats($userId, 1, 0, 0, 0, 100, 10, 0, 0, 0, 0, 0, 0, 0, null);
        // Set structures to have non-zero levels for Dark Matter, Naquadah, Protoform production
        $userStructures = new UserStructure($userId, 0, 0, 0, 0, 0, 0, 0, 0, 0, 10, 10, 10, 10, 10, 10, 0); // All at level 10

        // 3. Set up the consecutive calls for the mock
        $activeEdicts = [new UserEdict(1, $userId, $edictKey, '2023-01-01')];
        $this->edictRepo->method('findActiveByUserId')
            ->with($userId)
            ->willReturnOnConsecutiveCalls(
                [],             // First call returns no edicts (for baseline)
                $activeEdicts   // Second call returns the active edict (for boosted)
            );
        
        // 4. Calculate baseline income
        $this->powerCalculator->clearCache();
        $baselineIncome = $this->powerCalculator->calculateIncomePerTurn($userId, $userResources, $userStats, $userStructures);

        // 5. Calculate income with the edict active
        $this->powerCalculator->clearCache(); 
        $boostedIncome = $this->powerCalculator->calculateIncomePerTurn($userId, $userResources, $userStats, $userStructures);

        // 6. Assert the effects for Dark Matter, Naquadah, and Protoform
        // Dark Matter
        $this->assertEqualsWithDelta(
            $baselineIncome['dark_matter_income'] * 1.20,
            $boostedIncome['dark_matter_income'],
            0.0001,
            "Boosted Dark Matter income should be exactly 20% higher than the baseline."
        );

        // Naquadah
        $this->assertEqualsWithDelta(
            $baselineIncome['naquadah_income'] * 1.20,
            $boostedIncome['naquadah_income'],
            0.0001,
            "Boosted Naquadah income should be exactly 20% higher than the baseline."
        );

        // Protoform
        $this->assertEqualsWithDelta(
            $baselineIncome['protoform_income'] * 1.20,
            $boostedIncome['protoform_income'],
            0.0001,
            "Boosted Protoform income should be exactly 20% higher than the baseline."
        );
    }

    public function testFerengiPrincipleEdictEffects()
    {
        $userId = 1;
        $edictKey = 'ferengi_principle';

        // 1. Define the edict and its modifiers
        $edictDefinition = new EdictDefinition(
            $edictKey,
            'The Ferengi Principle',
            '', '', 'economic',
            [
                'credit_income_percent' => 0.15,
                'defense_power_percent' => -0.10
            ],
            0, 'credits'
        );
        $this->edictRepo->method('getDefinition')->with($edictKey)->willReturn($edictDefinition);

        // 2. Create mock user data (with all necessary args)
        $userResources = new UserResource($userId, 100000, 1000, 1000, 1000.0, 1000, 100, 100, 100, 100, 100, 0, 0, 0, 0.0);
        $userStats = new UserStats($userId, 1, 0, 0, 0, 100, 10, 0, 0, 10, 10, 0, 0, 0, null);
        $userStructures = new UserStructure($userId, 10, 0, 10, 0, 10, 0, 0, 10, 0, 0, 0, 0, 0, 0, 0, 1);

        // 3. Set up the consecutive calls for the mock
        $activeEdicts = [new UserEdict(1, $userId, $edictKey, '2023-01-01')];
        $this->edictRepo->method('findActiveByUserId')
            ->with($userId)
            ->willReturnOnConsecutiveCalls(
                [],             // First call returns no edicts (for baseline income)
                [],             // Second call returns no edicts (for baseline defense)
                $activeEdicts,  // Third call returns the active edict (for boosted income)
                $activeEdicts   // Fourth call returns the active edict (for boosted defense)
            );
        
        // 4. Calculate baseline income and defense power
        $this->powerCalculator->clearCache();
        $baselineIncome = $this->powerCalculator->calculateIncomePerTurn($userId, $userResources, $userStats, $userStructures);

        $this->powerCalculator->clearCache();
        $baselineDefense = $this->powerCalculator->calculateDefensePower($userId, $userResources, $userStats, $userStructures);

        // 5. Calculate boosted income and defense power with the edict active
        $this->powerCalculator->clearCache();
        $boostedIncome = $this->powerCalculator->calculateIncomePerTurn($userId, $userResources, $userStats, $userStructures);
        
        $this->powerCalculator->clearCache();
        $boostedDefense = $this->powerCalculator->calculateDefensePower($userId, $userResources, $userStats, $userStructures);

        // Recalculate expected values precisely based on PowerCalculatorService logic and config
        // Credit Income calculation components
        $econIncome = 10 * 100000; // economy_upgrade_level * credit_income_per_econ_level
        $workerIncome = 100 * 100; // workers * credit_income_per_worker
        $baseProduction = $econIncome + $workerIncome; // 1010000

        $statBonusPct = 10 * 0.01; // wealth_points * credit_bonus_per_wealth_point (0.1)
        $accountingFirmBonusPct = 0.01 * 10 * pow(1.05, 9); // accounting_firm_base_bonus * level * (multiplier ^ (level-1)) (0.1551328...)

        // Baseline Credit Income
        $baselineAdditiveMultiplier = 1 + $statBonusPct + $accountingFirmBonusPct; // 1 + 0.1 + 0.1551328... = 1.2551328...
        $expectedBaselineCreditIncome = (int)floor($baseProduction * $baselineAdditiveMultiplier); // (int)floor(1010000 * 1.2551328...) = 1267684

        // Boosted Credit Income (with edict)
        $edictCreditMultiplier = 0.15; // From Ferengi Principle
        $boostedAdditiveMultiplier = $baselineAdditiveMultiplier + $edictCreditMultiplier; // 1.2551328... + 0.15 = 1.4051328...
        $expectedBoostedCreditIncome = (int)floor($baseProduction * $boostedAdditiveMultiplier); // (int)floor(1010000 * 1.4051328...) = 1419184

        // Defense Power calculation components
        $guardBasePower = 100 * 1.0; // guards * power_per_guard
        $totalBaseDefensePower = $guardBasePower; // no armory/general flat bonuses in mock

        $fortBonusPct = 10 * 0.1; // fortification_level * power_per_fortification_level (1.0)
        $defBonusPct = 10 * 0.1; // defense_upgrade_level * power_per_defense_level (1.0)
        $structureBonusPercent = $fortBonusPct + $defBonusPct; // 2.0

        $statDefenseBonusPct = 10 * 0.1; // constitution_points * power_per_constitution_point (1.0)
        
        // Baseline Defense Power
        $baselineDefenseMultiplier = 1 + $structureBonusPercent + $statDefenseBonusPct; // 1 + 2.0 + 1.0 = 4.0
        $expectedBaselineDefensePower = (int)floor($totalBaseDefensePower * $baselineDefenseMultiplier); // (int)floor(100 * 4.0) = 400

        // Boosted Defense Power (with edict)
        $edictDefenseBonusPct = -0.10; // From Ferengi Principle
        $boostedDefenseMultiplier = $baselineDefenseMultiplier + $edictDefenseBonusPct; // 4.0 + (-0.10) = 3.9
        $expectedBoostedDefensePower = (int)floor($totalBaseDefensePower * $boostedDefenseMultiplier); // (int)floor(100 * 3.9) = 390

        // Assert the effects
        // Credit Income: +15% (effectively, within the additive multiplier context)
        $this->assertEquals(
            $expectedBoostedCreditIncome,
            $boostedIncome['total_credit_income'],
            "Boosted credit income should match precise calculation."
        );

        // Defense Power: -10% (effectively, within the additive multiplier context)
        $this->assertEquals(
            $expectedBoostedDefensePower,
            $boostedDefense['total'],
            "Boosted defense power should match precise calculation."
        );
    }
}
