<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\PowerCalculatorService;
use App\Models\Services\TurnProcessorService;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceBankLogRepository;
use App\Models\Repositories\AllianceStructureRepository;
use App\Models\Repositories\AllianceStructureDefinitionRepository;
use App\Models\Repositories\GeneralRepository;
use App\Models\Repositories\ScientistRepository;
use App\Models\Repositories\EdictRepository;
use App\Models\Services\EmbassyService;
use App\Models\Services\ArmoryService;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStructure;
use App\Models\Entities\UserStats;
use App\Core\Config;
use Mockery;

class ProtoformLoopTest extends TestCase
{
    public function testCalculateIncome_IncludesProtoformIncome(): void
    {
        // 1. Arrange Dependencies
        $mockConfig = Mockery::mock(Config::class);
        $mockArmoryService = Mockery::mock(ArmoryService::class);
        $mockAllianceStructRepo = Mockery::mock(AllianceStructureRepository::class);
        $mockStructDefRepo = Mockery::mock(AllianceStructureDefinitionRepository::class);
        $mockEdictRepo = Mockery::mock(EdictRepository::class);
        $mockGeneralRepo = Mockery::mock(GeneralRepository::class);

        $mockEdictRepo->shouldReceive('findActiveByUserId')->andReturn([]);
        $mockGeneralRepo->shouldReceive('findByUserId')->andReturn([]);
        $mockGeneralRepo->shouldReceive('countByUserId')->andReturn(0);
        $mockConfig->shouldReceive('get')->with('game_balance.generals', [])->andReturn([]);

        $service = new PowerCalculatorService(
            $mockConfig,
            $mockArmoryService,
            $mockAllianceStructRepo,
            $mockStructDefRepo,
            $mockEdictRepo,
            $mockGeneralRepo
        );

        $userId = 1;

        // Mock Config
        $mockConfig->shouldReceive('get')
            ->with('game_balance.turn_processor')
            ->andReturn([
                'protoform_per_vat_level' => 5,
                'credit_income_per_econ_level' => 1000,
                'credit_income_per_worker' => 10,
                'credit_bonus_per_wealth_point' => 0.01,
                'bank_interest_rate' => 0.01,
                'citizen_growth_per_pop_level' => 10
            ]);

        $mockArmoryService->shouldReceive('getAggregateBonus')->andReturn(0);

        $resources = new UserResource($userId, 0, 0, 0, 0.0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.0);
        $stats = new UserStats($userId, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, null);
        $structures = new UserStructure($userId, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 10);

        // 2. Act
        $result = $service->calculateIncomePerTurn($userId, $resources, $stats, $structures, null);

        // 3. Assert
        $this->assertEquals(50, $result['protoform_income']);
    }

    public function testProcessTurn_AppliesProtoformIncomeAndUpkeep(): void
    {
        // 1. Arrange Dependencies
        $mockDb = $this->createMockPDO();
        $mockConfig = Mockery::mock(Config::class);
        $mockUserRepo = Mockery::mock(UserRepository::class);
        $mockResourceRepo = Mockery::mock(ResourceRepository::class);
        $mockStructureRepo = Mockery::mock(StructureRepository::class);
        $mockStatsRepo = Mockery::mock(StatsRepository::class);
        $mockPowerCalc = Mockery::mock(PowerCalculatorService::class);
        $mockAllianceRepo = Mockery::mock(AllianceRepository::class);
        $mockBankLogRepo = Mockery::mock(AllianceBankLogRepository::class);
        $mockGeneralRepo = Mockery::mock(GeneralRepository::class);
        $mockScientistRepo = Mockery::mock(ScientistRepository::class);
        $mockEdictRepo = Mockery::mock(EdictRepository::class);
        $mockEmbassyService = Mockery::mock(EmbassyService::class);

        $userId = 1;

        // Mock Config
        $mockConfig->shouldReceive('get')->with('game_balance.alliance_treasury', [])->andReturn([]);
        $mockConfig->shouldReceive('get')->with('game_balance.upkeep', [])->andReturn([
            'general' => ['protoform' => 10],
            'scientist' => ['protoform' => 5]
        ]);

        $mockEdictRepo->shouldReceive('findActiveByUserId')->andReturn([]);

        $service = new TurnProcessorService(
            $mockDb,
            $mockConfig,
            $mockUserRepo,
            $mockResourceRepo,
            $mockStructureRepo,
            $mockStatsRepo,
            $mockPowerCalc,
            $mockAllianceRepo,
            $mockBankLogRepo,
            $mockGeneralRepo,
            $mockScientistRepo,
            $mockEdictRepo,
            $mockEmbassyService
        );

        $mockUserRepo->shouldReceive('getAllUserIds')->andReturn([$userId]);
        $mockUser = new \App\Models\Entities\User(
            id: $userId, 
            email: 'test@example.com', 
            characterName: 'TestUser', 
            bio: null, 
            profile_picture_url: null, 
            phone_number: null, 
            alliance_id: null, 
            alliance_role_id: null, 
            passwordHash: 'hash', 
            createdAt: '2025-01-01',
            is_npc: false
        );
        $mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($mockUser);

        // Mock Data Fetches
        $resources = new UserResource($userId, 0, 0, 0, 0.0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0.0);
        $structures = new UserStructure($userId, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
        $stats = new UserStats($userId, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, null);

        $mockResourceRepo->shouldReceive('findByUserId')->andReturn($resources);
        $mockStructureRepo->shouldReceive('findByUserId')->andReturn($structures);
        $mockStatsRepo->shouldReceive('findByUserId')->andReturn($stats);

        // Mock Power Calculation
        $mockIncomeData = [
            'total_credit_income' => 1000,
            'interest' => 50,
            'total_citizens' => 10,
            'research_data_income' => 0,
            'dark_matter_income' => 0,
            'naquadah_income' => 0,
            'protoform_income' => 50
        ];
        $mockPowerCalc->shouldReceive('calculateIncomePerTurn')->andReturn($mockIncomeData);

        // Mock Upkeep
        $mockGeneralRepo->shouldReceive('countByUserId')->with($userId)->andReturn(1);
        $mockScientistRepo->shouldReceive('getActiveScientistCount')->with($userId)->andReturn(2);

        // Mock DB Transaction
        $mockDb->shouldReceive('inTransaction')->andReturn(false);
        $mockDb->shouldReceive('beginTransaction')->once();
        $mockDb->shouldReceive('commit')->once();

        // Mock Alliance Processing (Empty for this test)
        $mockAllianceRepo->shouldReceive('getAllAlliances')->andReturn([]);

        // 2. Expect Apply Turn Income & Upkeep
        $mockResourceRepo->shouldReceive('applyTurnIncome')
            ->once()
            ->with($userId, 1000, 50, 10, 0, 0, 0, 50)
            ->andReturn(true);
            
        // Upkeep: 1 General (10) + 2 Scientists (5*2=10) = 20
        $mockResourceRepo->shouldReceive('updateResources')
            ->once()
            ->with($userId, null, null, null, -20)
            ->andReturn(true);

        $mockStatsRepo->shouldReceive('applyTurnAttackTurn')->once()->andReturn(true);

        // 3. Act
        $service->processAllUsers();
        
        // Assertions handled by Mockery expectations
        $this->assertTrue(true);
    }
}
