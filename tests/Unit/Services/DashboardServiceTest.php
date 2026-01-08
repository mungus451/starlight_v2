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

use App\Models\Repositories\NotificationRepository;
use App\Models\Repositories\BountyRepository;
use App\Models\Repositories\WarRepository;
use App\Models\Repositories\BattleRepository; // Added
use App\Models\Services\AdvisorService;
use App\Models\Services\NetWorthCalculatorService;

class DashboardServiceTest extends TestCase
{
    private DashboardService $service;
    private UserRepository|Mockery\MockInterface $mockUserRepo;
    private ResourceRepository|Mockery\MockInterface $mockResourceRepo;
    private StatsRepository|Mockery\MockInterface $mockStatsRepo;
    private StructureRepository|Mockery\MockInterface $mockStructureRepo;
    private EffectRepository|Mockery\MockInterface $mockEffectRepo;
    private NotificationRepository|Mockery\MockInterface $mockNotificationRepo;
    private AdvisorService|Mockery\MockInterface $mockAdvisorService;
    private BountyRepository|Mockery\MockInterface $mockBountyRepo;
    private WarRepository|Mockery\MockInterface $mockWarRepo;
    private BattleRepository|Mockery\MockInterface $mockBattleRepo; // Added
    private PowerCalculatorService|Mockery\MockInterface $mockPowerCalc;
    private NetWorthCalculatorService|Mockery\MockInterface $mockNwCalc;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockUserRepo = Mockery::mock(UserRepository::class);
        $this->mockResourceRepo = Mockery::mock(ResourceRepository::class);
        $this->mockStatsRepo = Mockery::mock(StatsRepository::class);
        $this->mockStructureRepo = Mockery::mock(StructureRepository::class);
        $this->mockEffectRepo = Mockery::mock(EffectRepository::class);
        $this->mockNotificationRepo = Mockery::mock(NotificationRepository::class);
        $this->mockAdvisorService = Mockery::mock(AdvisorService::class);
        $this->mockBountyRepo = Mockery::mock(BountyRepository::class);
        $this->mockWarRepo = Mockery::mock(WarRepository::class);
        $this->mockBattleRepo = Mockery::mock(BattleRepository::class); // Added
        $this->mockPowerCalc = Mockery::mock(PowerCalculatorService::class);
        $this->mockNwCalc = Mockery::mock(NetWorthCalculatorService::class);

        // Setup default behaviors for new mocks to avoid null errors during execution if called
        $this->mockNotificationRepo->shouldReceive('getRecent')->andReturn([])->byDefault();
        $this->mockAdvisorService->shouldReceive('getSuggestions')->andReturn([])->byDefault();
        $this->mockBountyRepo->shouldReceive('getHighestActiveBounty')->andReturn(null)->byDefault();
        $this->mockWarRepo->shouldReceive('findActiveWarByAllianceId')->andReturn(null)->byDefault();
        $this->mockBattleRepo->shouldReceive('getPaginatedUserBattles')->andReturn([])->byDefault(); // Added

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
            $this->mockBattleRepo, // Added
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
        $this->mockStatsRepo->shouldReceive('findRivalByNetWorth')->with($stats)->andReturn(null); // Added Expectation
        $this->mockStructureRepo->shouldReceive('findByUserId')->with($userId)->andReturn($struct);
        $this->mockEffectRepo->shouldReceive('getAllActiveEffects')->with($userId)->andReturn([]);
        $this->mockNwCalc->shouldReceive('calculateTotalNetWorth')->with($userId)->andReturn(1000.0);

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
