<?php

namespace App\Core;

use DI\Container;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use PDO;

/**
 * ContainerFactory
 * * Responsible for building and configuring the Dependency Injection Container.
 * It registers the core services (Database, Session, Config) so they can be
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
            
            // 1. Database Connection (PDO)
            // We map the PDO class to our existing Database singleton.
            // Whenever a class asks for 'PDO', it gets this instance.
            PDO::class => function (ContainerInterface $c) {
                return Database::getInstance();
            },

            // 2. Session
            // Wraps the global $_SESSION
            Session::class => function (ContainerInterface $c) {
                return new Session();
            },

            // 3. Configuration Loader
            // Loads settings from /config/*.php
            Config::class => function (ContainerInterface $c) {
                return new Config();
            },

            // 4. CSRF Service
            // Handles security tokens
            CSRFService::class => function (ContainerInterface $c) {
                return new CSRFService();
            },
        ]);

        return $builder->build();
    }
}