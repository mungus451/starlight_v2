<?php

namespace App\Core;

use DI\Container;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use PDO;
use Redis;
use Exception;
use App\Models\Repositories\NotificationRepository;
use App\Models\Services\NotificationService;
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

            // 3. Redis Connection
            Redis::class => function (ContainerInterface $c) {
                $config = $c->get(Config::class);
                $redisConfig = $config->get('redis');

                $redis = new Redis();
                
                if (!@$redis->connect($redisConfig['host'], $redisConfig['port'])) {
                    throw new Exception("Could not connect to Redis at {$redisConfig['host']}:{$redisConfig['port']}");
                }

                if (!empty($redisConfig['password'])) {
                    if (!$redis->auth($redisConfig['password'])) {
                        throw new Exception("Redis authentication failed.");
                    }
                }

                if (isset($redisConfig['database'])) {
                    $redis->select($redisConfig['database']);
                }

                if (!empty($redisConfig['prefix'])) {
                    $redis->setOption(Redis::OPT_PREFIX, $redisConfig['prefix']);
                }

                return $redis;
            },

            // 4. Session
            Session::class => function (ContainerInterface $c) {
                return new Session();
            },

            // 5. CSRF Service
            CSRFService::class => function (ContainerInterface $c) {
                return new CSRFService(
                    $c->get(Redis::class),
                    $c->get(Session::class)
                );
            },

            // 6. Notification System
            NotificationRepository::class => function (ContainerInterface $c) {
                return new NotificationRepository($c->get(PDO::class));
            },

            NotificationService::class => function (ContainerInterface $c) {
                return new NotificationService($c->get(NotificationRepository::class));
            },

            // 7. Currency Converter
            \App\Models\Repositories\HouseFinanceRepository::class => function (ContainerInterface $c) {
                return new \App\Models\Repositories\HouseFinanceRepository($c->get(PDO::class));
            },
            \App\Models\Services\CurrencyConverterService::class => function (ContainerInterface $c) {
                return new \App\Models\Services\CurrencyConverterService(
                    $c->get(\App\Models\Repositories\ResourceRepository::class),
                    $c->get(\App\Models\Repositories\HouseFinanceRepository::class),
                    $c->get(PDO::class)
                );
            },

            // 8. Event Dispatcher (The Hub)
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

            // 9. Input Validator
            Validator::class => function (ContainerInterface $c) {
                return new Validator();
            },

            // 10. Default System Logger (Standard app logs)
            Logger::class => function (ContainerInterface $c) {
                $logPath = __DIR__ . '/../../logs/app.log';
                return new Logger($logPath, false);
            },

            // 11. Specific NPC Logger (Writes to npc_actions.log AND stdout for CLI)
            'NpcLogger' => function (ContainerInterface $c) {
                $logPath = __DIR__ . '/../../logs/npc_actions.log';
                // True = Echo to Stdout (CLI)
                return new Logger($logPath, true);
            },
        ]);

        return $builder->build();
    }
}