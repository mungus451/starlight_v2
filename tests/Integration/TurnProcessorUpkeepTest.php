<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Models\Services\TurnProcessorService;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Services\PowerCalculatorService;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceBankLogRepository;
use App\Models\Repositories\GeneralRepository;
use App\Models\Repositories\ScientistRepository;
use App\Models\Repositories\EdictRepository;
use App\Models\Services\EmbassyService;
use App\Models\Entities\User;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStructure;
use App\Models\Entities\UserStats;
use App\Models\Entities\UserEdict;
use App\Models\Entities\EdictDefinition;
use App\Core\Config;
use PDO;
use ReflectionMethod;

class TurnProcessorUpkeepTest extends TestCase
{
    private $turnProcessorService;
    private $resourceRepo;
    private $edictRepo;
    
    protected function setUp(): void
    {
        // Mock all dependencies for TurnProcessorService
        $pdo = $this->createMock(PDO::class);
        $config = $this->createMock(Config::class);
        $userRepo = $this->createMock(UserRepository::class);
        $this->resourceRepo = $this->createMock(ResourceRepository::class);
        $structureRepo = $this->createMock(StructureRepository::class);
        $statsRepo = $this->createMock(StatsRepository::class);
        $powerCalculator = $this->createMock(PowerCalculatorService::class);
        $allianceRepo = $this->createMock(AllianceRepository::class);
        $bankLogRepo = $this->createMock(AllianceBankLogRepository::class);
        $generalRepo = $this->createMock(GeneralRepository::class);
        $scientistRepo = $this->createMock(ScientistRepository::class);
        $this->edictRepo = $this->createMock(EdictRepository::class);
        $embassyService = $this->createMock(EmbassyService::class);

        // Configure mock repo counts to return 0 to isolate edict upkeep
        $generalRepo->method('countByUserId')->willReturn(0);
        $scientistRepo->method('getActiveScientistCount')->willReturn(0);

        // Configure mock config
        $config->method('get')->willReturnMap([
            ['game_balance.alliance_treasury', [], []],
            ['game_balance.upkeep', [], ['general' => ['protoform' => 0], 'scientist' => ['protoform' => 0]]]
        ]);

        // Mock the findById and findByUserId methods to return valid entity objects
        $userId = 1;
        $userRepo->method('findById')->with($userId)->willReturn(new User($userId, 'test@test.com', 'test', null, null, null, null, null, '', ''));
        $this->resourceRepo->method('findByUserId')->with($userId)->willReturn(new UserResource($userId, 1000000, 0, 0, 0, 0, 100, 0, 0, 0, 0, 0, 0, 0, 0));
        $structureRepo->method('findByUserId')->with($userId)->willReturn(new UserStructure($userId, 0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0));
        $statsRepo->method('findByUserId')->with($userId)->willReturn(new UserStats($userId, 1,0,0,0,0,0,0,0,0,0,0,0,0,null));
        
        // Mock power calculator to return zero income to isolate upkeep
        $powerCalculator->method('calculateIncomePerTurn')->willReturn([
            'total_credit_income' => 0,
            'interest' => 0,
            'total_citizens' => 0,
            'research_data_income' => 0,
            'dark_matter_income' => 0,
            'naquadah_income' => 0,
            'protoform_income' => 0,
        ]);
        
        $this->turnProcessorService = new TurnProcessorService(
            $pdo, $config, $userRepo, $this->resourceRepo, $structureRepo, $statsRepo,
            $powerCalculator, $allianceRepo, $bankLogRepo, $generalRepo, $scientistRepo,
            $this->edictRepo, $embassyService
        );
    }

    public function testSyntheticIntegrationWorkerUpkeep()
    {
        $userId = 1;
        $edictKey = 'synthetic_integration';
        $workerCount = 100;
        $upkeepPerWorker = 500;
        $expectedUpkeep = $workerCount * $upkeepPerWorker; // 50000

        // 1. Mock the active edict and its definition
        $activeEdicts = [new UserEdict(1, $userId, $edictKey, '2023-01-01')];
        $edictDefinition = new EdictDefinition(
            $edictKey, 'Synthetic Integration', '', '', 'economic',
            ['worker_upkeep_flat' => $upkeepPerWorker], 0, 'credits'
        );
        $this->edictRepo->method('findActiveByUserId')->with($userId)->willReturn($activeEdicts);
        $this->edictRepo->method('getDefinition')->with($edictKey)->willReturn($edictDefinition);

        // 2. Expect the updateResources method to be called with the correct upkeep deduction
        $this->resourceRepo->expects($this->once())
            ->method('updateResources')
            ->with(
                $this->equalTo($userId),
                $this->equalTo(-$expectedUpkeep), // Expecting a negative value for deduction
                $this->equalTo(0),
                $this->equalTo(0)
            );

        // 3. Run the turn processor for the user by making the private method accessible
        $method = new ReflectionMethod(TurnProcessorService::class, 'processTurnForUser');
        $method->invoke($this->turnProcessorService, $userId);
    }
}
