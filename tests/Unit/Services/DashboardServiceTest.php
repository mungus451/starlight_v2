<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\DashboardService;
use App\Models\Services\PowerCalculatorService;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\EffectRepository;
use App\Models\Entities\User;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStats;
use App\Models\Entities\UserStructure;
use Mockery;

class DashboardServiceTest extends TestCase
{
    private DashboardService $service;
    private UserRepository|Mockery\MockInterface $mockUserRepo;
    private ResourceRepository|Mockery\MockInterface $mockResourceRepo;
    private StatsRepository|Mockery\MockInterface $mockStatsRepo;
    private StructureRepository|Mockery\MockInterface $mockStructureRepo;
    private EffectRepository|Mockery\MockInterface $mockEffectRepo;
    private PowerCalculatorService|Mockery\MockInterface $mockPowerCalc;
    private NetWorthCalculatorService|Mockery\MockInterface $mockNwCalc;

    private NotificationRepository|Mockery\MockInterface $mockNotificationRepo;
    private AdvisorService|Mockery\MockInterface $mockAdvisorService;
    private BountyRepository|Mockery\MockInterface $mockBountyRepo;
    private WarRepository|Mockery\MockInterface $mockWarRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockUserRepo = Mockery::mock(UserRepository::class);
        $this->mockResourceRepo = Mockery::mock(ResourceRepository::class);
        $this->mockStatsRepo = Mockery::mock(StatsRepository::class);
        $this->mockStructureRepo = Mockery::mock(StructureRepository::class);
        $this->mockEffectRepo = Mockery::mock(EffectRepository::class);
        $this->mockNotificationRepo = Mockery::mock(\App\Models\Repositories\NotificationRepository::class);
        $this->mockAdvisorService = Mockery::mock(\App\Models\Services\AdvisorService::class);
        $this->mockBountyRepo = Mockery::mock(\App\Models\Repositories\BountyRepository::class);
        $this->mockWarRepo = Mockery::mock(\App\Models\Repositories\WarRepository::class);
        $this->mockPowerCalc = Mockery::mock(PowerCalculatorService::class);
        $this->mockNwCalc = Mockery::mock(\App\Models\Services\NetWorthCalculatorService::class);

        $this->service = new DashboardService(
            $this->mockUserRepo,
            $this->mockResourceRepo,
            $this->mockStatsRepo,
            $this->mockStructureRepo,
            $this->mockEffectRepo,
            $this->mockNotificationRepo,
            $this->mockAdvisorService,
            $this->mockBountyRepo,
            $this->mockWarRepo,
            $this->mockPowerCalc,
            $this->mockNwCalc
        );
    }

    public function testGetDashboardDataReturnsAllKeys(): void
    {
        $userId = 1;
        $allianceId = 10;

        $user = $this->createMockUser($userId, $allianceId);
        $res = $this->createMockResource($userId);
        $stats = $this->createMockStats($userId);
        $struct = $this->createMockStructure($userId);

        $this->mockUserRepo->shouldReceive('findById')->with($userId)->andReturn($user);
        $this->mockResourceRepo->shouldReceive('findByUserId')->with($userId)->andReturn($res);
        $this->mockStatsRepo->shouldReceive('findByUserId')->with($userId)->andReturn($stats);
        $this->mockStructureRepo->shouldReceive('findByUserId')->with($userId)->andReturn($struct);
        $this->mockEffectRepo->shouldReceive('getAllActiveEffects')->with($userId)->andReturn([]);
        $this->mockNwCalc->shouldReceive('calculateTotalNetWorth')->with($userId)->andReturn(1000.0);
        $this->mockAdvisorService->shouldReceive('getSuggestions')->with($user)->andReturn([]);
        $this->mockNotificationRepo->shouldReceive('getRecent')->with($userId, 3)->andReturn([]);
        
        $this->mockStatsRepo->shouldReceive('findRivalByNetWorth')->with($stats)->andReturn(null);
        $this->mockBountyRepo->shouldReceive('getHighestActiveBounty')->andReturn(null);
        $this->mockWarRepo->shouldReceive('findActiveWarByAllianceId')->with($allianceId)->andReturn(null);

        // Verify Calculations are called with correct Alliance ID
        $this->mockPowerCalc->shouldReceive('calculateIncomePerTurn')
            ->once()->with($userId, $res, $stats, $struct, $allianceId)->andReturn([
                'naquadah_income' => 0,
                'total_credit_income' => 0,
                'interest' => 0,
                'total_citizens' => 0
            ]);
        
        $this->mockPowerCalc->shouldReceive('calculateOffensePower')->once()->andReturn([]);
        $this->mockPowerCalc->shouldReceive('calculateDefensePower')->once()->andReturn([]);
        $this->mockPowerCalc->shouldReceive('calculateSpyPower')->once()->andReturn([]);
        $this->mockPowerCalc->shouldReceive('calculateSentryPower')->once()->andReturn([]);

        $result = $this->service->getDashboardData($userId);

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('incomeBreakdown', $result);
        $this->assertArrayHasKey('offenseBreakdown', $result);
    }

    // --- Helpers ---

    private function createMockUser(int $id, ?int $allianceId): User
    {
        return new User(
            id: $id,
            email: 'test@test.com',
            characterName: 'TestUser',
            bio: null,
            profile_picture_url: null,
            phone_number: null,
            alliance_id: $allianceId,
            alliance_role_id: null,
            passwordHash: 'hash',
            createdAt: '2024-01-01',
            is_npc: false
        );
    }

    private function createMockResource(int $userId): UserResource
    {
        return new UserResource($userId, 0, 0, 0, 0.0, 0, 0, 0, 0, 0, 0);
    }

    private function createMockStats(int $userId): UserStats
    {
        return new UserStats($userId, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, null, 0, 0, 0, 0);
    }

    private function createMockStructure(int $userId): UserStructure
    {
        return new UserStructure($userId, 0, 0, 0, 0, 0, 0, 0, 0);
    }
}
