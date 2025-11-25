<?php

// tests/AllianceStructureBonusTest.php

/**
 * TEST: Verify that alliance structure bonuses (specifically citizen_growth_flat)
 * are properly applied during turn processing.
 */

if (php_sapi_name() !== 'cli') {
    die('Access Denied: CLI only.');
}

require __DIR__ . '/../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    die("Error: Could not find .env file.\n");
}

use App\Core\ContainerFactory;
use App\Models\Services\PowerCalculatorService;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Repositories\AllianceStructureRepository;
use App\Models\Repositories\AllianceStructureDefinitionRepository;

echo "\n" . str_repeat("=", 60) . "\n";
echo "   ALLIANCE STRUCTURE BONUS TEST: citizen_growth_flat\n";
echo str_repeat("=", 60) . "\n";

try {
    // 1. Build Container
    echo "[1/8] Booting Container... ";
    $container = ContainerFactory::createContainer();
    $db = $container->get(PDO::class);
    echo "OK\n";

    // 2. Resolve Dependencies
    echo "[2/8] Resolving Services... ";
    $powerCalcService = $container->get(PowerCalculatorService::class);
    $userRepo = $container->get(UserRepository::class);
    $resourceRepo = $container->get(ResourceRepository::class);
    $statsRepo = $container->get(StatsRepository::class);
    $structRepo = $container->get(StructureRepository::class);
    $allianceRepo = $container->get(AllianceRepository::class);
    $roleRepo = $container->get(AllianceRoleRepository::class);
    $allianceStructRepo = $container->get(AllianceStructureRepository::class);
    $structDefRepo = $container->get(AllianceStructureDefinitionRepository::class);
    echo "OK\n";

    // 3. Setup Test Data (in transaction for rollback)
    echo "[3/8] Seeding Test Scenario...\n";
    $db->beginTransaction();

    // Create a test user
    $testUserId = $userRepo->createUser(
        'alliance_bonus_test_' . time() . '@example.com',
        'AllianceBonusTest_' . time(),
        'hash'
    );
    echo "      - Test User Created (ID: $testUserId)\n";

    // Create test user data
    $resourceRepo->createDefaults($testUserId);
    $statsRepo->createDefaults($testUserId);
    $structRepo->createDefaults($testUserId);

    // Create an alliance
    $allianceId = $allianceRepo->create('TestAlliance_' . time(), 'TA' . substr(time(), -2), $testUserId);
    echo "      - Alliance Created (ID: $allianceId)\n";

    // Create a role and assign user to alliance
    $roleId = $roleRepo->create($allianceId, 'Leader', 0, []);
    $userRepo->setAlliance($testUserId, $allianceId, $roleId);
    echo "      - User assigned to alliance with role\n";

    // 4. Test without alliance structure
    echo "[4/8] Testing income WITHOUT alliance structure...\n";
    $resources = $resourceRepo->findByUserId($testUserId);
    $stats = $statsRepo->findByUserId($testUserId);
    $structures = $structRepo->findByUserId($testUserId);

    $incomeWithoutBonus = $powerCalcService->calculateIncomePerTurn(
        $testUserId,
        $resources,
        $stats,
        $structures,
        $allianceId
    );
    echo "      - Base citizen income: {$incomeWithoutBonus['base_citizen_income']}\n";
    echo "      - Alliance citizen bonus: {$incomeWithoutBonus['alliance_citizen_bonus']}\n";
    echo "      - Total citizens/turn: {$incomeWithoutBonus['total_citizens']}\n";

    // 5. Add population_habitat structure at level 1
    echo "[5/8] Adding Population Habitat structure (Level 1)...\n";
    $allianceStructRepo->createOrUpgrade($allianceId, 'population_habitat', 1);
    echo "      - population_habitat added at level 1\n";

    // 6. Test WITH alliance structure (level 1)
    echo "[6/8] Testing income WITH alliance structure (Level 1)...\n";
    $incomeWithBonus = $powerCalcService->calculateIncomePerTurn(
        $testUserId,
        $resources,
        $stats,
        $structures,
        $allianceId
    );
    echo "      - Base citizen income: {$incomeWithBonus['base_citizen_income']}\n";
    echo "      - Alliance citizen bonus: {$incomeWithBonus['alliance_citizen_bonus']}\n";
    echo "      - Total citizens/turn: {$incomeWithBonus['total_citizens']}\n";

    // 7. Upgrade structure to level 2 and test scaling
    echo "[7/8] Testing with structure at Level 2...\n";
    $allianceStructRepo->createOrUpgrade($allianceId, 'population_habitat', 2);
    
    $incomeLevel2 = $powerCalcService->calculateIncomePerTurn(
        $testUserId,
        $resources,
        $stats,
        $structures,
        $allianceId
    );
    echo "      - Base citizen income: {$incomeLevel2['base_citizen_income']}\n";
    echo "      - Alliance citizen bonus: {$incomeLevel2['alliance_citizen_bonus']}\n";
    echo "      - Total citizens/turn: {$incomeLevel2['total_citizens']}\n";

    // 8. Verify results
    echo "[8/8] Verifying Results...\n";

    // Check: Alliance bonus should be 0 before structure
    if ($incomeWithoutBonus['alliance_citizen_bonus'] !== 0) {
        throw new Exception("FAILED: Alliance bonus should be 0 without structure, got: {$incomeWithoutBonus['alliance_citizen_bonus']}");
    }
    echo "      - [PASS] Alliance bonus is 0 without structure\n";

    // Check: Alliance bonus should be 5 with level 1 structure (5 * 1 = 5)
    if ($incomeWithBonus['alliance_citizen_bonus'] !== 5) {
        throw new Exception("FAILED: Alliance bonus should be 5 with level 1 structure, got: {$incomeWithBonus['alliance_citizen_bonus']}");
    }
    echo "      - [PASS] Alliance bonus is 5 with level 1 structure\n";

    // Check: Alliance bonus should be 10 with level 2 structure (5 * 2 = 10)
    if ($incomeLevel2['alliance_citizen_bonus'] !== 10) {
        throw new Exception("FAILED: Alliance bonus should be 10 with level 2 structure, got: {$incomeLevel2['alliance_citizen_bonus']}");
    }
    echo "      - [PASS] Alliance bonus is 10 with level 2 structure\n";

    // Check: Total citizens should include the alliance bonus
    $expectedTotal = $incomeLevel2['base_citizen_income'] + $incomeLevel2['alliance_citizen_bonus'];
    if ($incomeLevel2['total_citizens'] !== $expectedTotal) {
        throw new Exception("FAILED: Total citizens should be $expectedTotal, got: {$incomeLevel2['total_citizens']}");
    }
    echo "      - [PASS] Total citizens includes alliance bonus correctly\n";

    // Rollback test data
    $db->rollBack();
    echo "\n      - DB Transaction Rolled Back\n";

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "   ✅ SUCCESS: Alliance structure bonuses are working correctly\n";
    echo str_repeat("=", 60) . "\n";
    exit(0);

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "\n❌ TEST FAILED: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
