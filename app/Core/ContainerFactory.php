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

/**
 * ContainerFactory
 * * Responsible for building and configuring the Dependency Injection Container.
 * It registers the core services (Database, Session, Config, Redis) so they can be
 * automatically injected into any Controller or Service.
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
                
                // Attempt connection
                if (!@$redis->connect($redisConfig['host'], $redisConfig['port'])) {
                    throw new Exception("Could not connect to Redis at {$redisConfig['host']}:{$redisConfig['port']}");
                }

                // Authenticate
                if (!empty($redisConfig['password'])) {
                    if (!$redis->auth($redisConfig['password'])) {
                        throw new Exception("Redis authentication failed.");
                    }
                }

                // Select database
                if (isset($redisConfig['database'])) {
                    $redis->select($redisConfig['database']);
                }

                // Set global prefix
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
            // Explicitly registering these ensures they are available for injection
            NotificationRepository::class => function (ContainerInterface $c) {
                return new NotificationRepository($c->get(PDO::class));
            },

            NotificationService::class => function (ContainerInterface $c) {
                return new NotificationService($c->get(NotificationRepository::class));
            },
        ]);

        return $builder->build();
    }
}