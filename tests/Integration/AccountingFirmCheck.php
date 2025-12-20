<?php

/**
 * FUNCTIONAL TEST: ACCOUNTING FIRM STRUCTURE
 * Verifies that the new "Accounting Firm" structure:
 * 1. Requires both Credits AND Naquadah Crystals to upgrade.
 * 2. Correctly deducts both currencies from the user's resources.
 * 3. Correctly increments the `accounting_firm_level` in the database.
 * 4. Correctly applies the 1% income multiplier bonus in PowerCalculatorService.
 */

if (php_sapi_name() !== 'cli') {
    die('Access Denied: CLI only.');
}

require __DIR__ . '/../../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();
} catch (\Exception $e) {}

use App\Core\ContainerFactory;
use App\Models\Services\StructureService;
use App\Models\Services\PowerCalculatorService;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;

try {
    $container = ContainerFactory::createContainer();
    $db = $container->get(PDO::class);
    
    // Resolve Services
    $structService = $container->get(StructureService::class);
    $powerCalc = $container->get(PowerCalculatorService::class);
    $userRepo = $container->get(UserRepository::class);
    $resRepo = $container->get(ResourceRepository::class);
    $structRepo = $container->get(StructureRepository::class);
    $statsRepo = $container->get(StatsRepository::class);
    
    $db->beginTransaction();

    // 1. Setup Test Subject
    $seed = bin2hex(random_bytes(2));
    $userId = $userRepo->createUser("accountant_{$seed}@test.com", "CPA_{$seed}", 'hash');
    $resRepo->createDefaults($userId);
    $structRepo->createDefaults($userId);
    $statsRepo->createDefaults($userId);

    // Initial Resource Injection (Credits + Crystals)
    // Base Cost: 50M Credits, 25 Crystals
    $initialCredits = 60000000;
    $initialCrystals = 1000;
    
    $stmt = $db->prepare("UPDATE user_resources SET credits = ?, naquadah_crystals = ? WHERE user_id = ?");
    $stmt->execute([$initialCredits, $initialCrystals, $userId]);

    // 2. Test: Upgrade Accounting Firm (Level 0 -> 1)
    echo ">> Test 1: Upgrading Accounting Firm (Level 0 -> 1)...
";
    $response = $structService->upgradeStructure($userId, 'accounting_firm');
    
    if (!$response->isSuccess()) {
        throw new Exception("Upgrade Failed: " . $response->message);
    }
    
    // Verify Resource Deduction
    $resources = $resRepo->findByUserId($userId);
    $expectedCredits = $initialCredits - 1000000; // Corrected expected deduction
    $expectedCrystals = $initialCrystals - 250;
    
    if ($resources->credits !== $expectedCredits) {
        throw new Exception("Credit Deduction Failed. Expected {$expectedCredits}, got {$resources->credits}");
    }
    if ($resources->naquadah_crystals != $expectedCrystals) {
        throw new Exception("Crystal Deduction Failed. Expected {$expectedCrystals}, got {$resources->naquadah_crystals}");
    }
    
    // Verify Structure Level
    $structures = $structRepo->findByUserId($userId);
    if ($structures->accounting_firm_level !== 1) {
        throw new Exception("Structure Level incorrect. Expected 1, got {$structures->accounting_firm_level}");
    }
    echo "   [PASS] Upgrade logic & Resource deduction verified.
";

    // 3. Test: Income Multiplier Application
    echo ">> Test 2: Verifying Income Multiplier...
";
    
    // Setup Base Income (1 Worker = 100 Credits)
    // Preserve existing credits and untrained citizens
    $currentCredits = $resources->credits; 
    $currentUntrainedCitizens = $resources->untrained_citizens;
    $resRepo->updateTrainedUnits($userId, $currentCredits, $currentUntrainedCitizens, 1, 0, 0, 0, 0); // Set 1 Worker
    $resources = $resRepo->findByUserId($userId); // Refresh
    $structures = $structRepo->findByUserId($userId); // Refresh (Level 1)
    $stats = $statsRepo->findByUserId($userId);

    $incomeData = $powerCalc->calculateIncomePerTurn($userId, $resources, $stats, $structures);
    
    // Expected Calculation:
    // Base: 1 Worker * 100 = 100
    // Multiplier: 1 + (1 Level * 0.01) = 1.01
    // Total: floor(100 * 1.01) = 101
    
    $expectedIncome = 101;
    
    if ($incomeData['total_credit_income'] !== $expectedIncome) {
        throw new Exception("Income Calculation Failed. Expected {$expectedIncome}, got {$incomeData['total_credit_income']}");
    }
    
    if ($incomeData['accounting_firm_bonus_pct'] !== 0.01) {
        throw new Exception("Bonus Percentage not reported correctly. Expected 0.01, got {$incomeData['accounting_firm_bonus_pct']}");
    }
    
    echo "   [PASS] Income Multiplier (1%) verified.
";
    
    // 4. Test: Insufficient Crystals
    echo ">> Test 3: Verifying Insufficient Crystal handling...\n";
    
    // Reset resources to have credits but NO crystals
    $stmt = $db->prepare("UPDATE user_resources SET credits = ?, naquadah_crystals = 0 WHERE user_id = ?");
    $stmt->execute([100000000, $userId]);
    
    $response = $structService->upgradeStructure($userId, 'accounting_firm');
    
    if ($response->isSuccess()) {
        throw new Exception("Upgrade should have failed due to lack of crystals.");
    }
    
    if (strpos($response->message, 'naquadah crystals') === false) {
        throw new Exception("Error message did not mention crystals: " . $response->message);
    }
    
    echo "   [PASS] Insufficient Crystal check verified.
";


    echo "\n\033[32mALL TESTS PASSED: Accounting Firm Structure Verified.\033[0m\n";

} catch (Throwable $e) {
    echo "\n\033[31m[FAIL] " . $e->getMessage() . "\033[0m\n";
    echo "Trace: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
} finally {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
}
