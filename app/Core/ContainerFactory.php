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

/**
 * ContainerFactory
 * * Responsible for building and configuring the Dependency Injection Container.
 * * Now configures the EventDispatcher and binds Domain Events to Listeners.
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

            // 7. Event Dispatcher (The Hub)
            EventDispatcher::class => function (ContainerInterface $c) {
                $dispatcher = new EventDispatcher();

                // --- Register Listeners for BattleConcludedEvent ---
                // 1. Send Notification to Defender
                $dispatcher->addListener(
                    BattleConcludedEvent::class,
                    $c->get(BattleNotificationListener::class)
                );

                // 2. Log War Battle (if applicable)
                $dispatcher->addListener(
                    BattleConcludedEvent::class,
                    $c->get(WarLoggerListener::class)
                );

                return $dispatcher;
            },
        ]);

        return $builder->build();
    }
}