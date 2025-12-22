<?php

require __DIR__ . '/../../vendor/autoload.php';

use App\Core\ContainerFactory;
use App\Models\Services\PowerCalculatorService;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\StructureRepository;

try {
    $container = ContainerFactory::createContainer();
    $service = $container->get(PowerCalculatorService::class);
    
    // Mock Data
    $userRepo = $container->get(UserRepository::class);
    $resRepo = $container->get(ResourceRepository::class);
    $structRepo = $container->get(StructureRepository::class);
    $statsRepo = $container->get(StatsRepository::class);
    
    // Find a user or create dummy
    $userId = 7; // Leonardo (NPC)
    $res = $resRepo->findByUserId($userId);
    $stats = $statsRepo->findByUserId($userId);
    $struct = $structRepo->findByUserId($userId);
    
    echo "Testing Offense...\n";
    $service->calculateOffensePower($userId, $res, $stats, $struct);
    echo "Offense OK.\n";
    
    echo "Testing Defense...\n";
    $service->calculateDefensePower($userId, $res, $stats, $struct);
    echo "Defense OK.\n";
    
    echo "Testing Income...\n";
    $service->calculateIncomePerTurn($userId, $res, $stats, $struct);
    echo "Income OK.\n";
    
    echo "Testing Spy...\n";
    $service->calculateSpyPower($userId, $res, $struct);
    echo "Spy OK.\n";
    
    echo "Testing Sentry...\n";
    $service->calculateSentryPower($userId, $res, $struct);
    echo "Sentry OK.\n";
    
    echo "ALL METHODS PASSED.\n";

} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
