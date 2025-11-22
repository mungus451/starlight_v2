<?php

namespace App\Core;

use DI\Container;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use PDO;
use Redis;
use Exception;

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
            // Loads settings from /config/*.php
            Config::class => function (ContainerInterface $c) {
                return new Config();
            },

            // 2. Database Connection (PDO)
            PDO::class => function (ContainerInterface $c) {
                return Database::getInstance();
            },

            // 3. Redis Connection
            // Configures and connects the Redis client
            Redis::class => function (ContainerInterface $c) {
                $config = $c->get(Config::class);
                $redisConfig = $config->get('redis');

                $redis = new Redis();
                
                // Attempt connection
                // Suppress warning to handle connection failure gracefully via Exception
                if (!@$redis->connect($redisConfig['host'], $redisConfig['port'])) {
                    throw new Exception("Could not connect to Redis at {$redisConfig['host']}:{$redisConfig['port']}");
                }

                // Authenticate if password is set
                if (!empty($redisConfig['password'])) {
                    if (!$redis->auth($redisConfig['password'])) {
                        throw new Exception("Redis authentication failed.");
                    }
                }

                // Select database
                if (isset($redisConfig['database'])) {
                    $redis->select($redisConfig['database']);
                }

                // Set global prefix if defined
                if (!empty($redisConfig['prefix'])) {
                    $redis->setOption(Redis::OPT_PREFIX, $redisConfig['prefix']);
                }

                return $redis;
            },

            // 4. Session
            // Wraps the global $_SESSION
            Session::class => function (ContainerInterface $c) {
                return new Session();
            },

            // 5. CSRF Service
            // Handles security tokens - Now correctly injects Redis and Session
            CSRFService::class => function (ContainerInterface $c) {
                return new CSRFService(
                    $c->get(Redis::class),
                    $c->get(Session::class)
                );
            },
        ]);

        return $builder->build();
    }
}