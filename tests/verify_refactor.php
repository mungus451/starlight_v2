<?php

// tests/verify_refactor.php

if (php_sapi_name() !== 'cli') {
    die('Access Denied: CLI only.');
}

// 1. Bootstrap
require __DIR__ . '/../vendor/autoload.php';

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
use App\Models\Repositories\StructureRepository; // --- ADDED ---
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\WarRepository;
use App\Models\Repositories\AllianceRoleRepository;

echo "\n" . str_repeat("=", 50) . "\n";
echo "   STARLIGHT DOMINION: REFACTOR VERIFICATION (FIXED v2)\n";
echo str_repeat("=", 50) . "\n";

try {
    // 2. Build Container
    echo "[1/6] Booting Container... ";
    $container = ContainerFactory::createContainer();
    $db = $container->get(PDO::class);
    echo "OK\n";

    // 3. Resolve Dependencies
    echo "[2/6] Resolving Services... ";
    $attackService = $container->get(AttackService::class);
    $userRepo = $container->get(UserRepository::class);
    $resRepo = $container->get(ResourceRepository::class);
    $statsRepo = $container->get(StatsRepository::class);
    $structRepo = $container->get(StructureRepository::class); // --- ADDED ---
    $allianceRepo = $container->get(AllianceRepository::class);
    $warRepo = $container->get(WarRepository::class);
    $roleRepo = $container->get(AllianceRoleRepository::class);
    echo "OK\n";

    // 4. Setup Test Data
    echo "[3/6] Seeding Test Scenario...\n";
    $db->beginTransaction(); // We will rollback at the end to keep DB clean

    // Create Users First
    $attackerId = $userRepo->createUser('test_attacker@example.com', 'TestAttacker_' . time(), 'hash');
    $defenderId = $userRepo->createUser('test_defender@example.com', 'TestDefender_' . time(), 'hash');
    echo "      - Users Created (Attacker: $attackerId, Defender: $defenderId)\n";

    // Create Alliances with Real Leaders
    $allyA = $allianceRepo->create('TestEmpireA', 'TEA', $attackerId);
    $allyB = $allianceRepo->create('TestEmpireB', 'TEB', $defenderId);
    echo "      - Alliances Created (A: $allyA, B: $allyB)\n";
    
    // Create Roles
    $roleA = $roleRepo->create($allyA, 'Member', 1, []);
    $roleB = $roleRepo->create($allyB, 'Member', 1, []);

    // Declare War
    $warId = $warRepo->createWar('Test War', $allyA, $allyB, 'Testing Refactor', 'units_killed', 1000);
    echo "      - War Created (ID: $warId)\n";

    // Link Users to Alliances
    $userRepo->setAlliance($attackerId, $allyA, $roleA);
    $userRepo->setAlliance($defenderId, $allyB, $roleB);

    // Setup Stats/Resources/Structures (CRITICAL FIX)
    $resRepo->createDefaults($attackerId);
    $resRepo->createDefaults($defenderId);
    
    $statsRepo->createDefaults($attackerId);
    $statsRepo->createDefaults($defenderId);
    
    $structRepo->createDefaults($attackerId); // --- ADDED ---
    $structRepo->createDefaults($defenderId); // --- ADDED ---

    // Give Attacker Ammo
    $resRepo->updateBattleAttacker($attackerId, 100000, 500); // 500 Soldiers
    $statsRepo->updateAttackTurns($attackerId, 10); // 10 Turns

    // Give Defender Defense
    $resRepo->updateBattleDefender($defenderId, 100000, 100); // 100 Guards
    
    // 5. Execute Attack
    echo "[4/6] Executing AttackService...\n";
    
    $targetUser = $userRepo->findById($defenderId);
    
    $result = $attackService->conductAttack($attackerId, $targetUser->characterName, 'plunder');
    
    if (!$result) {
        throw new Exception("AttackService returned false!");
    }
    echo "      - Attack Executed Successfully.\n";

    // 6. Verify Results
    echo "[5/6] Verifying Event Listeners...\n";

    // Check A: Battle Report
    $stmt = $db->prepare("SELECT id FROM battle_reports WHERE attacker_id = ? AND defender_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$attackerId, $defenderId]);
    $report = $stmt->fetch();
    if (!$report) throw new Exception("FAILED: No Battle Report generated.");
    echo "      - [PASS] Battle Report ID: " . $report['id'] . "\n";

    // Check B: Notification
    $stmt = $db->prepare("SELECT id, title FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$defenderId]);
    $notif = $stmt->fetch();
    
    if (!$notif) throw new Exception("FAILED: No Notification found for defender.");
    if (strpos($notif['title'], 'You were attacked') === false) throw new Exception("FAILED: Notification title mismatch.");
    echo "      - [PASS] Notification dispatched to Defender (ID: {$notif['id']})\n";

    // Check C: War Log
    $stmt = $db->prepare("SELECT id FROM war_battle_logs WHERE war_id = ? AND battle_report_id = ?");
    $stmt->execute([$warId, $report['id']]);
    $log = $stmt->fetch();
    if (!$log) throw new Exception("FAILED: War Log not created.");
    echo "      - [PASS] War Battle Log created (ID: {$log['id']})\n";

    echo "[6/6] Cleanup...\n";
    $db->rollBack();
    echo "      - DB Transaction Rolled Back (Test Data Purged)\n";

    echo "\n" . str_repeat("=", 50) . "\n";
    echo "   ✅ VERIFICATION SUCCESSFUL: The Event System is Working\n";
    echo str_repeat("=", 50) . "\n";

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "\n❌ TEST FAILED: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}