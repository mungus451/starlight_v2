<?php

/**
* GAME LOOP SIMULATION TEST
*
* Functional verification of TurnProcessorService.
* Ensures massive batch processing works with DTOs and atomic updates.
*
* Usage: php tests/GameLoopSimulationTest.php
*/

if (php_sapi_name() !== 'cli') {
die('Access Denied: CLI only.');
}

require __DIR__ . '/../../vendor/autoload.php';

try {
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
die("Error: Could not find .env file.\n");
}

use App\Core\ContainerFactory;
use App\Core\Config;
use App\Models\Services\TurnProcessorService;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\StructureRepository;

echo "\n" . str_repeat("=", 60) . "\n";
echo " STARLIGHT DOMINION: TURN PROCESSOR SIMULATION\n";
echo str_repeat("=", 60) . "\n\n";

try {
$container = ContainerFactory::createContainer();
$db = $container->get(PDO::class);
$config = $container->get(Config::class);
$turnService = $container->get(TurnProcessorService::class);

$userRepo = $container->get(UserRepository::class);
$resRepo = $container->get(ResourceRepository::class);
$statsRepo = $container->get(StatsRepository::class);
$structRepo = $container->get(StructureRepository::class);

// Start Transaction
$db->beginTransaction();

// 1. Create Test Subject
$uid = $userRepo->createUser("turn_test_" . uniqid() . "@test.com", "TurnSubject", 'hash');
$resRepo->createDefaults($uid);
$statsRepo->createDefaults($uid);
$structRepo->createDefaults($uid);

// 2. Set Up Conditions
// - Economy Lvl 10 (Income)
// - Population Lvl 10 (Citizens)
// - Banked Credits 1M (Interest)
// - Attack Turns 10
$structRepo->updateStructureLevel($uid, 'economy_upgrade_level', 10);
$structRepo->updateStructureLevel($uid, 'population_level', 10);
$resRepo->updateBankingCredits($uid, 0, 1000000);
$statsRepo->updateAttackTurns($uid, 10);

echo "Initial State Created (User ID: $uid)\n";

// 3. Run Turn Processor (Scoped to all users, but we check ours)
echo "Running Turn Processor... ";
$counts = $turnService->processAllUsers();
echo "OK (Processed {$counts['users']} users)\n";

// 4. Verify Results
$resAfter = $resRepo->findByUserId($uid);
$statsAfter = $statsRepo->findByUserId($uid);

// Check Attack Turns (Should be +1)
echo "Checking Attack Turns... ";
if ($statsAfter->attack_turns !== 11) {
throw new Exception("Turn Fail: Expected 11 turns, got {$statsAfter->attack_turns}");
}
echo "OK\n";

// Check Citizens (Base 1 * Lvl 10 = +10)
// Default 250 + 10 = 260
echo "Checking Citizen Growth... ";
if ($resAfter->untrained_citizens !== 260) {
throw new Exception("Turn Fail: Expected 260 citizens, got {$resAfter->untrained_citizens}");
}
echo "OK\n";

// Check Income & Interest
echo "Checking Income/Interest... ";

// Dynamically calculate expectations based on Config
$turnConfig = $config->get('game_balance.turn_processor');
$econIncome = $turnConfig['credit_income_per_econ_level'] * 10; // 1000 * 10 = 10,000
$interestRate = $turnConfig['bank_interest_rate']; // 0.00005

// Income goes to Hand
$expectedHand = $econIncome; // 10,000

// Interest goes to Bank
$interestEarned = (int)floor(1000000 * $interestRate); // 1,000,000 * 0.00005 = 50
$expectedBank = 1000000 + $interestEarned; // 1,000,050

if ($resAfter->credits !== $expectedHand) {
throw new Exception("Turn Fail: Expected {$expectedHand} credits on hand, got {$resAfter->credits}");
}

if ($resAfter->banked_credits !== $expectedBank) {
// Debug info
echo "\n [Debug] Rate: {$interestRate}, Earned: {$interestEarned}\n";
throw new Exception("Turn Fail: Expected {$expectedBank} banked credits, got {$resAfter->banked_credits}");
}
echo "OK\n";

echo "\n✅ SUCCESS: Game Loop handles immutable state correctly.\n";

} catch (Throwable $e) {
echo "\n❌ FAIL: " . $e->getMessage() . "\n";
exit(1);
} finally {
if (isset($db) && $db->inTransaction()) {
$db->rollBack();
echo "\n(Test Transaction Rolled Back)\n";
}
}