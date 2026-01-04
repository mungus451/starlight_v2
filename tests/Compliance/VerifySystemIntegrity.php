--- START OF FILE tests/VerifySystemIntegrity.php ---

<?php

/**
 * SYSTEM INTEGRITY VERIFICATION
 *
 * Complementary to the Architecture Audit and MVC Lint.
 * This script verifies:
 * 1. Physical existence of critical architectural components (Presenters, DTOs).
 * 2. PSR-4 Autoloading integrity (Classes match filenames).
 * 3. Dependency Injection Wiring (Smoke test).
 *
 * Usage: php tests/VerifySystemIntegrity.php
 */

if (php_sapi_name() !== 'cli') {
    die('Access Denied: CLI only.');
}

require __DIR__ . '/../../vendor/autoload.php';

use App\Core\ContainerFactory;

class IntegrityTester
{
    private int $checks = 0;
    private int $failures = 0;
    private $container;

    public function run(): void
    {
        $this->printHeader();

        // 1. Initialize Container (Smoke Test for Core)
        $this->check("Booting DI Container", function() {
            $this->container = ContainerFactory::createContainer();
            return $this->container instanceof \DI\Container;
        });

        // 2. Critical Directory Structure
        $this->section("Directory Structure");
        $requiredDirs = [
            'app/Controllers',
            'app/Models/Entities',
            'app/Models/Repositories',
            'app/Models/Services',
            'app/Presenters', // Crucial for MVC-S
            'app/Core',
            'views'
        ];
        foreach ($requiredDirs as $dir) {
            $this->checkFile("Directory: {$dir}", __DIR__ . '/../../' . $dir);
        }

        // 3. Critical Class Existence (Refactor Artifacts)
        $this->section("Critical Refactor Artifacts");
        $requiredClasses = [
            // Presenters (View Logic)
            \App\Presenters\StructurePresenter::class,
            \App\Presenters\BattleReportPresenter::class,
            \App\Presenters\SpyReportPresenter::class,
            \App\Presenters\AllianceProfilePresenter::class,
        \App\Presenters\ArmoryPresenter::class,         // Armory View Logic
        \App\Core\ServiceResponse::class,               // DTO for Service->Controller
        \App\Models\Services\ViewContextService::class, // BaseController Dependency
        \App\Core\JsonResponse::class                   // Uniform JSON Output
    ];

        foreach ($requiredClasses as $class) {
            $this->checkClass($class);
        }

        // 4. Runtime Instantiation Check (Wiring)
        $this->section("Dependency Injection Wiring (Smoke Test)");
        $servicesToTest = [
            \App\Models\Services\AttackService::class, // Heavy dependencies
            \App\Models\Services\TurnProcessorService::class, // Core loop
            \App\Controllers\DashboardController::class, // UI Controller
        ];

        foreach ($servicesToTest as $service) {
            $this->check("Resolving " . $this->shortName($service), function() use ($service) {
                try {
                    $instance = $this->container->get($service);
                    return $instance instanceof $service;
                } catch (Throwable $e) {
                    echo "      -> Error: " . $e->getMessage() . "\n";
                    return false;
                }
            });
        }

        $this->printSummary();
    }

    // --- Helpers ---

    private function check(string $label, callable $test): void
    {
        $this->checks++;
        echo str_pad("  Checking {$label}...", 60, '.');
        
        try {
            $result = $test();
            if ($result) {
                echo "\033[32m PASS \033[0m\n";
            } else {
                echo "\033[31m FAIL \033[0m\n";
                $this->failures++;
            }
        } catch (Throwable $e) {
            echo "\033[31m ERROR \033[0m\n";
            echo "    -> " . $e->getMessage() . "\n";
            $this->failures++;
        }
    }

    private function checkFile(string $label, string $path): void
    {
        $this->check($label, fn() => file_exists($path));
    }

    private function checkClass(string $class): void
    {
        $this->check("Class: " . $this->shortName($class), fn() => class_exists($class));
    }

    private function shortName(string $class): string
    {
        $parts = explode('\\', $class);
        return end($parts);
    }

    private function section(string $name): void
    {
        echo "\n\033[1;33m[ {$name} ]\033[0m\n";
    }

    private function printHeader(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "   STARLIGHT DOMINION: SYSTEM INTEGRITY VERIFICATION\n";
        echo str_repeat("=", 60) . "\n";
    }

    private function printSummary(): void
    {
        echo str_repeat("-", 60) . "\n";
        echo "Checks: {$this->checks} | Failures: {$this->failures}\n\n";

        if ($this->failures === 0) {
            echo "\033[32m✅ SYSTEM INTEGRITY CONFIRMED.\033[0m\n";
            exit(0);
        } else {
            echo "\033[31m❌ SYSTEM INTEGRITY FAILED.\033[0m\n";
            exit(1);
        }
    }
}

$tester = new IntegrityTester();
$tester->run();