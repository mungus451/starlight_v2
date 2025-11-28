<?php

/**
* BATTLE SIMULATION TEST
*
* Functional verification of the AttackService -> Repository -> DTO flow.
* Ensures that immutable Entities are handled correctly during state transitions.
*
* Usage: php tests/BattleSimulationTest.php
*/

if (php_sapi_name() !== 'cli') {
die('Access Denied: CLI only.');
}

require __DIR__ . '/../vendor/autoload.php';

// Load Env
try {
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
die("Error: Could not find .env file.\n");
}

use App\Core\ContainerFactory;
use App\Models\Services\AttackService;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\BattleRepository;

echo "\n" . str_repeat("=", 60) . "\n";
echo " STARLIGHT DOMINION: BATTLE SIMULATION (DTO CHECK)\n";
echo str_repeat("=", 60) . "\n\n";

try {
// 1. Boot Container
echo "[1/5] Booting Container... ";
$container = ContainerFactory::createContainer();
$db = $container->get(PDO::class);
$attackService = $container->get(AttackService::class);

// Repos
$userRepo = $container->get(UserRepository::class);
$resRepo = $container->get(ResourceRepository::class);
$statsRepo = $container->get(StatsRepository::class);
$structRepo = $container->get(StructureRepository::class);
$battleRepo = $container->get(BattleRepository::class);

echo "OK\n";

// 2. Setup Test Data (Transaction Rollback Strategy)
echo "[2/5] Seeding Test Combatants... ";
$db->beginTransaction();

// Attacker
$atkName = "SimAttacker_" . uniqid();
$atkId = $userRepo->createUser("atk_" . uniqid() . "@test.com", $atkName, 'hash');
$resRepo->createDefaults($atkId);
$statsRepo->createDefaults($atkId);
$structRepo->createDefaults($atkId);

// Arm Attacker
$resRepo->updateBattleAttacker($atkId, 1000000, 500); // 500 Soldiers
$statsRepo->updateAttackTurns($atkId, 50);
$structRepo->updateStructureLevel($atkId, 'offense_upgrade_level', 5);

// Defender
$defName = "SimDefender_" . uniqid();
$defId = $userRepo->createUser("def_" . uniqid() . "@test.com", $defName, 'hash');
$resRepo->createDefaults($defId);
$statsRepo->createDefaults($defId);
$structRepo->createDefaults($defId);

// Arm Defender
$resRepo->updateBattleDefender($defId, 5000000, 200); // 5M Credits, 200 Guards
$structRepo->updateStructureLevel($defId, 'fortification_level', 5);

echo "OK (Atk: $atkId, Def: $defId)\n";

// 3. Pre-Flight Checks
echo "[3/5] Verifying Immutable State... ";
$attacker = $userRepo->findById($atkId);
$defender = $userRepo->findById($defId);

// PHP 8.2+ Check: verify class is actually readonly via Reflection
$ref = new ReflectionClass($attacker);
if (!$ref->isReadOnly()) {
throw new Exception("Entity Integrity Fail: User class is NOT readonly.");
}

// Verify Resource Hydration
$atkRes = $resRepo->findByUserId($atkId);
if ($atkRes->soldiers !== 500) {
throw new Exception("Hydration Fail: Expected 500 soldiers, got {$atkRes->soldiers}");
}
echo "OK\n";

// 4. Execute Attack
echo "[4/5] Executing AttackService... ";

// We expect the service to calculate outcomes and UPDATE the DB via repositories
// It should NOT try to set properties on the Entity objects.
$response = $attackService->conductAttack($atkId, $defName, 'plunder');

if (!$response->isSuccess()) {
throw new Exception("Attack Failed: " . $response->message);
}
echo "OK\n";
echo " > Result: " . $response->message . "\n";

// 5. Verify Post-Conditions
echo "[5/5] Verifying Persistence... ";

// Check Turns Deducted
$atkStatsAfter = $statsRepo->findByUserId($atkId);
if ($atkStatsAfter->attack_turns >= 50) {
throw new Exception("State Update Fail: Attack turns not deducted (Current: {$atkStatsAfter->attack_turns})");
}

// Check Loot/Losses
$atkResAfter = $resRepo->findByUserId($atkId);
$defResAfter = $resRepo->findByUserId($defId);

// Assuming a win (500 soldiers vs 200 guards usually wins)
// If Attacker won, they should have > 1M credits (loot) and < 500 soldiers (losses)
if (str_contains($response->message, 'Victory')) {
if ($atkResAfter->credits <= 1000000) {
throw new Exception("Logic Fail: Attacker did not gain credits from victory.");
}
if ($atkResAfter->soldiers >= 500) {
throw new Exception("Logic Fail: Attacker took 0 casualties (unlikely with current config).");
}
if ($defResAfter->credits >= 5000000) {
throw new Exception("Logic Fail: Defender did not lose credits.");
}
}

// Check Report Generation
$reports = $battleRepo->findReportsByAttackerId($atkId);
if (empty($reports)) {
throw new Exception("Persistence Fail: No battle report generated.");
}

// Check Report DTO
$report = $reports[0];
$refReport = new ReflectionClass($report);
if (!$refReport->isReadOnly()) {
throw new Exception("Entity Integrity Fail: BattleReport is NOT readonly.");
}

echo "OK\n";
echo "\n✅ SUCCESS: Battle System functions correctly with Immutable Entities.\n";

} catch (Throwable $e) {
echo "\n❌ FAIL: " . $e->getMessage() . "\n";
echo "Trace: " . $e->getTraceAsString() . "\n";
exit(1);
} finally {
// Always rollback test data
if (isset($db) && $db->inTransaction()) {
$db->rollBack();
echo "\n(Test Transaction Rolled Back)\n";
}
}