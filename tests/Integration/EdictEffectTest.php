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

        // Instantiate the real service with mocked repositories
        $this->powerCalculator = new PowerCalculatorService(
            $config,
            $armoryService,
            $allianceStructRepo,
            $structDefRepo,
            $this->edictRepo,
            $generalRepo
        );
    }

    public function testSyntheticIntegrationEdictResourceBonus()
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

        // 2. Create mock user data (with all 15 UserResource args)
        $userResources = new UserResource($userId, 1000, 1000, 1000, 1000.0, 1000, 1000, 1000, 1000, 1000, 1000, 0, 0, 0, 0.0);
        $userStats = new UserStats($userId, 1, 0, 0, 0, 100, 10, 0, 0, 0, 0, 0, 0, 0, null);
        $userStructures = new UserStructure($userId, 0, 0, 0, 0, 0, 0, 0, 0, 0, 10, 10, 0, 10, 10, 0, 0);

        // 3. Set up the consecutive calls for the mock
        $activeEdicts = [new UserEdict(1, $userId, $edictKey, '2023-01-01')];
        $this->edictRepo->method('findActiveByUserId')
            ->with($userId)
            ->willReturnOnConsecutiveCalls(
                [],             // First call returns no edicts
                $activeEdicts   // Second call returns the active edict
            );
        
        // 4. Calculate baseline income
        $this->powerCalculator->clearCache();
        $baselineIncome = $this->powerCalculator->calculateIncomePerTurn($userId, $userResources, $userStats, $userStructures);
        $baselineDarkMatter = $baselineIncome['dark_matter_income'];

        // 5. Calculate income with the edict active
        $this->powerCalculator->clearCache(); 
        $boostedIncome = $this->powerCalculator->calculateIncomePerTurn($userId, $userResources, $userStats, $userStructures);
        $boostedDarkMatter = $boostedIncome['dark_matter_income'];

        // 6. Assert the effect is exactly +20%
        $this->assertEqualsWithDelta(
            $baselineDarkMatter * 1.20, 
            $boostedDarkMatter, 
            0.0001, 
            "Boosted DM income should be exactly 20% higher than the baseline."
        );
    }
}
