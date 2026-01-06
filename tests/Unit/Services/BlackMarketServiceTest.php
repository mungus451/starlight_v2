<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\BlackMarketService;
use App\Core\Config;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\BountyRepository;
use App\Models\Repositories\BlackMarketLogRepository;
use App\Models\Repositories\HouseFinanceRepository;
use App\Models\Services\EffectService;
use App\Models\Services\AttackService;
use Mockery;
use PDO;

class BlackMarketServiceTest extends TestCase
{
    private BlackMarketService $service;
    private $mockDb;
    private $mockConfig;
    private $mockResourceRepo;
    private $mockStatsRepo;
    private $mockUserRepo;
    private $mockBountyRepo;
    private $mockAttackService;
    private $mockLogRepo;
    private $mockEffectService;
    private $mockHouseFinanceRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockDb = Mockery::mock(PDO::class);
        $this->mockConfig = Mockery::mock(Config::class);
        $this->mockResourceRepo = Mockery::mock(ResourceRepository::class);
        $this->mockStatsRepo = Mockery::mock(StatsRepository::class);
        $this->mockUserRepo = Mockery::mock(UserRepository::class);
        $this->mockBountyRepo = Mockery::mock(BountyRepository::class);
        $this->mockAttackService = Mockery::mock(AttackService::class);
        $this->mockLogRepo = Mockery::mock(BlackMarketLogRepository::class);
        $this->mockEffectService = Mockery::mock(EffectService::class);
        $this->mockHouseFinanceRepo = Mockery::mock(HouseFinanceRepository::class);

        $this->service = new BlackMarketService(
            $this->mockDb,
            $this->mockConfig,
            $this->mockResourceRepo,
            $this->mockStatsRepo,
            $this->mockUserRepo,
            $this->mockBountyRepo,
            $this->mockAttackService,
            $this->mockLogRepo,
            $this->mockEffectService,
            $this->mockHouseFinanceRepo
        );
    }

    public function testTerminateHighRiskProtocolSuccess(): void
    {
        $userId = 1;

        // 1. Check if active
        $this->mockEffectService->shouldReceive('hasActiveEffect')
            ->with($userId, 'high_risk_protocol')
            ->once()
            ->andReturn(true);

        // 2. Transaction
        $this->mockDb->shouldReceive('beginTransaction')->once();
        $this->mockDb->shouldReceive('commit')->once();

        // 3. Break Effect
        $this->mockEffectService->shouldReceive('breakEffect')
            ->with($userId, 'high_risk_protocol')
            ->once();

        // 4. Apply Cooldown
        $this->mockEffectService->shouldReceive('applyEffect')
            ->with($userId, 'safehouse_cooldown', 60)
            ->once();

        // 5. Log
        $this->mockLogRepo->shouldReceive('log')
            ->with($userId, 'terminate', 'none', 0, 'high_risk_protocol')
            ->once();

        $response = $this->service->terminateHighRiskProtocol($userId);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals("Protocol terminated. Safehouse systems require 1 hour to reboot.", $response->message);
    }

    public function testTerminateHighRiskProtocolFailsIfNotActive(): void
    {
        $userId = 1;

        $this->mockEffectService->shouldReceive('hasActiveEffect')
            ->with($userId, 'high_risk_protocol')
            ->once()
            ->andReturn(false);

        $response = $this->service->terminateHighRiskProtocol($userId);

        $this->assertFalse($response->isSuccess());
        $this->assertEquals("High Risk Protocol is not active.", $response->message);
    }
}
