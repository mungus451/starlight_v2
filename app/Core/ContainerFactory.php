<?php

namespace App\Core;

use DI\Container;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use PDO;
use Predis\Client;
use Exception;

// Repositories
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\ArmoryRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Repositories\ApplicationRepository;
use App\Models\Repositories\AllianceBankLogRepository;
use App\Models\Repositories\AllianceLoanRepository;
use App\Models\Repositories\AllianceStructureRepository;
use App\Models\Repositories\AllianceStructureDefinitionRepository;
use App\Models\Repositories\AllianceForumTopicRepository;
use App\Models\Repositories\AllianceForumPostRepository;
use App\Models\Repositories\TreatyRepository;
use App\Models\Repositories\RivalryRepository;
use App\Models\Repositories\WarRepository;
use App\Models\Repositories\WarBattleLogRepository;
use App\Models\Repositories\WarHistoryRepository;
use App\Models\Repositories\BattleRepository;
use App\Models\Repositories\SpyRepository;
use App\Models\Repositories\NotificationRepository;
use App\Models\Repositories\UserNotificationPreferencesRepository;
use App\Models\Repositories\HouseFinanceRepository;
use App\Models\Repositories\SecurityRepository;
use App\Models\Repositories\BountyRepository;
use App\Models\Repositories\BlackMarketLogRepository;
use App\Models\Repositories\GeneralRepository;
use App\Models\Repositories\ScientistRepository;
use App\Models\Repositories\EdictRepository;
use App\Models\Repositories\EffectRepository; // --- NEW ---
use App\Models\Repositories\IntelRepository;   // --- NEW ---

// Services
use App\Models\Services\AuthService;
use App\Models\Services\DashboardService;
use App\Models\Services\ProfileService;
use App\Models\Services\BankService;
use App\Models\Services\TrainingService;
use App\Models\Services\StructureService;
use App\Models\Services\ArmoryService;
use App\Models\Services\GeneralService;
use App\Models\Services\SettingsService;
use App\Models\Services\SpyService;
use App\Models\Services\AttackService;
use App\Models\Services\LevelUpService;
use App\Models\Services\AllianceService;
use App\Models\Services\AllianceManagementService;
use App\Models\Services\AlliancePolicyService;
use App\Models\Services\AllianceStructureService;
use App\Models\Services\AllianceForumService;
use App\Models\Services\DiplomacyService;
use App\Models\Services\EmbassyService;
use App\Models\Services\WarService;
use App\Models\Services\PowerCalculatorService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Services\NotificationService;
use App\Models\Services\CurrencyConverterService;
use App\Models\Services\LeaderboardService;
use App\Models\Services\BlackMarketService;
use App\Models\Services\ViewContextService;
use App\Models\Services\NpcService;
use App\Models\Services\TurnProcessorService;
use App\Models\Services\EffectService; // --- NEW ---

// Core & Events
use App\Core\Events\EventDispatcher;
use App\Events\BattleConcludedEvent;
use App\Listeners\BattleNotificationListener;
use App\Listeners\WarLoggerListener;
use App\Core\Validator;
use App\Core\Logger;

/**
* ContainerFactory
* * Responsible for building and configuring the Dependency Injection Container.
*/
class ContainerFactory
{
/**
* Builds the container with application definitions.
*
* @return Container
*/
public static function createContainer(): Container
{
$builder = new ContainerBuilder();

// Enable attributes for Injection (e.g. #[Inject('NpcLogger')])
$builder->useAttributes(true);

// Configure the container definitions
$builder->addDefinitions([

// 1. Configuration Loader
Config::class => function (ContainerInterface $c) {
return new Config();
},

// 2. Database Connection (PDO)
PDO::class => function (ContainerInterface $c) {
return Database::getInstance();
},

    // 3. Redis Connection (via Predis)
    Client::class => function (ContainerInterface $c) {
        $config = $c->get(Config::class);
        $redisConfig = $config->get('redis');

        // Predis Connection Parameters
        $params = [
            'scheme'   => 'tcp',
            'host'     => $redisConfig['host'],
            'port'     => $redisConfig['port'],
            'password' => $redisConfig['password'] ?? null,
            'database' => $redisConfig['database'] ?? 0,
        ];

        // Predis Client Options
        $options = [
            'prefix' => $redisConfig['prefix'] ?? 'starlight_v2:',
            'exceptions' => true,
        ];

        return new Client($params, $options);
    },

    // 4. Session
    Session::class => function (ContainerInterface $c) {
        return new Session();
    },

    // 5. CSRF Service
    CSRFService::class => function (ContainerInterface $c) {
        return new CSRFService(
            $c->get(Client::class),
            $c->get(Session::class)
        );
    },
// --- REPOSITORIES (Manual registration to ensure PDO injection) ---
UserRepository::class => function (ContainerInterface $c) { return new UserRepository($c->get(PDO::class)); },
ResourceRepository::class => function (ContainerInterface $c) { return new ResourceRepository($c->get(PDO::class)); },
StatsRepository::class => function (ContainerInterface $c) { return new StatsRepository($c->get(PDO::class)); },
StructureRepository::class => function (ContainerInterface $c) { return new StructureRepository($c->get(PDO::class)); },
ArmoryRepository::class => function (ContainerInterface $c) { return new ArmoryRepository($c->get(PDO::class)); },
AllianceRepository::class => function (ContainerInterface $c) { return new AllianceRepository($c->get(PDO::class)); },
AllianceRoleRepository::class => function (ContainerInterface $c) { return new AllianceRoleRepository($c->get(PDO::class)); },
ApplicationRepository::class => function (ContainerInterface $c) { return new ApplicationRepository($c->get(PDO::class)); },
AllianceBankLogRepository::class => function (ContainerInterface $c) { return new AllianceBankLogRepository($c->get(PDO::class)); },
AllianceLoanRepository::class => function (ContainerInterface $c) { return new AllianceLoanRepository($c->get(PDO::class)); },
AllianceStructureRepository::class => function (ContainerInterface $c) { return new AllianceStructureRepository($c->get(PDO::class)); },
AllianceStructureDefinitionRepository::class => function (ContainerInterface $c) { return new AllianceStructureDefinitionRepository($c->get(PDO::class)); },
AllianceForumTopicRepository::class => function (ContainerInterface $c) { return new AllianceForumTopicRepository($c->get(PDO::class)); },
AllianceForumPostRepository::class => function (ContainerInterface $c) { return new AllianceForumPostRepository($c->get(PDO::class)); },
TreatyRepository::class => function (ContainerInterface $c) { return new TreatyRepository($c->get(PDO::class)); },
RivalryRepository::class => function (ContainerInterface $c) { return new RivalryRepository($c->get(PDO::class)); },
WarRepository::class => function (ContainerInterface $c) { return new WarRepository($c->get(PDO::class)); },
WarBattleLogRepository::class => function (ContainerInterface $c) { return new WarBattleLogRepository($c->get(PDO::class)); },
WarHistoryRepository::class => function (ContainerInterface $c) { return new WarHistoryRepository($c->get(PDO::class)); },
BattleRepository::class => function (ContainerInterface $c) { return new BattleRepository($c->get(PDO::class)); },
SpyRepository::class => function (ContainerInterface $c) { return new SpyRepository($c->get(PDO::class)); },
NotificationRepository::class => function (ContainerInterface $c) { return new NotificationRepository($c->get(PDO::class)); },
UserNotificationPreferencesRepository::class => function (ContainerInterface $c) { return new UserNotificationPreferencesRepository($c->get(PDO::class)); },
HouseFinanceRepository::class => function (ContainerInterface $c) { return new HouseFinanceRepository($c->get(PDO::class)); },
SecurityRepository::class => function (ContainerInterface $c) { return new SecurityRepository($c->get(PDO::class)); },
BountyRepository::class => function (ContainerInterface $c) { return new BountyRepository($c->get(PDO::class)); },
BlackMarketLogRepository::class => function (ContainerInterface $c) { return new BlackMarketLogRepository($c->get(PDO::class)); },
GeneralRepository::class => function (ContainerInterface $c) { return new GeneralRepository($c->get(PDO::class)); },
ScientistRepository::class => function (ContainerInterface $c) { return new ScientistRepository($c->get(PDO::class)); },
EffectRepository::class => function (ContainerInterface $c) { return new EffectRepository($c->get(PDO::class)); }, // --- NEW ---
IntelRepository::class => function (ContainerInterface $c) { return new IntelRepository($c->get(PDO::class)); },   // --- NEW ---

// --- SERVICES ---

// Attack Service (Updated Phase 19)
AttackService::class => function (ContainerInterface $c) {
return new AttackService(
$c->get(PDO::class),
$c->get(Config::class),
$c->get(UserRepository::class),
$c->get(ResourceRepository::class),
$c->get(StructureRepository::class),
$c->get(StatsRepository::class),
$c->get(BattleRepository::class),
$c->get(AllianceRepository::class),
$c->get(AllianceBankLogRepository::class),
$c->get(BountyRepository::class),
$c->get(ArmoryService::class),
$c->get(PowerCalculatorService::class),
$c->get(LevelUpService::class),
$c->get(EventDispatcher::class),
$c->get(EffectService::class) // --- NEW ---
);
},

// Black Market Service (Needs Logging Update Phase 20)
BlackMarketService::class => function (ContainerInterface $c) {
return new BlackMarketService(
$c->get(PDO::class),
$c->get(Config::class),
$c->get(ResourceRepository::class),
$c->get(StatsRepository::class),
$c->get(UserRepository::class),
$c->get(BountyRepository::class),
$c->get(AttackService::class),
$c->get(BlackMarketLogRepository::class),
$c->get(EffectService::class) // --- NEW ---
);
},

// Effect Service
EffectService::class => function (ContainerInterface $c) {
    return new EffectService(
        $c->get(EffectRepository::class),
        $c->get(UserRepository::class)
    );
},

// Spy Service (Manual definition required now)
SpyService::class => function (ContainerInterface $c) {
    return new SpyService(
        $c->get(PDO::class),
        $c->get(Config::class),
        $c->get(UserRepository::class),
        $c->get(ResourceRepository::class),
        $c->get(StructureRepository::class),
        $c->get(StatsRepository::class),
        $c->get(SpyRepository::class),
        $c->get(ArmoryService::class),
        $c->get(PowerCalculatorService::class),
        $c->get(LevelUpService::class),
        $c->get(NotificationService::class),
        $c->get(EffectService::class)
    );
},

// Currency Converter (Needs Logging Update Phase 20)
CurrencyConverterService::class => function (ContainerInterface $c) {
return new CurrencyConverterService(
$c->get(ResourceRepository::class),
$c->get(HouseFinanceRepository::class),
$c->get(PDO::class),
$c->get(BlackMarketLogRepository::class) // Injected for Logging Phase
);
},

// Notifications
NotificationService::class => function (ContainerInterface $c) {
return new NotificationService(
    $c->get(NotificationRepository::class),
    $c->get(UserNotificationPreferencesRepository::class)
);
},

// Event Dispatcher (The Hub)
EventDispatcher::class => function (ContainerInterface $c) {
$dispatcher = new EventDispatcher();

$dispatcher->addListener(
BattleConcludedEvent::class,
$c->get(BattleNotificationListener::class)
);

$dispatcher->addListener(
BattleConcludedEvent::class,
$c->get(WarLoggerListener::class)
);

return $dispatcher;
},

// Input Validator
Validator::class => function (ContainerInterface $c) {
return new Validator();
},

// Default System Logger
Logger::class => function (ContainerInterface $c) {
$logPath = __DIR__ . '/../../logs/app.log';
return new Logger($logPath, false);
},

// Specific NPC Logger (Writes to npc_actions.log AND stdout for CLI)
'NpcLogger' => function (ContainerInterface $c) {
$logPath = __DIR__ . '/../../logs/npc_actions.log';
return new Logger($logPath, true); // True = Echo to Stdout (CLI)
},

TurnProcessorService::class => function (ContainerInterface $c) {
    return new TurnProcessorService(
        $c->get(PDO::class),
        $c->get(Config::class),
        $c->get(UserRepository::class),
        $c->get(ResourceRepository::class),
        $c->get(StructureRepository::class),
        $c->get(StatsRepository::class),
        $c->get(PowerCalculatorService::class),
        $c->get(AllianceRepository::class),
        $c->get(AllianceBankLogRepository::class),
        $c->get(GeneralRepository::class),
        $c->get(ScientistRepository::class),
        $c->get(EdictRepository::class),
        $c->get(EmbassyService::class)
    );
}
]);

return $builder->build();
}
}