--- START OF FILE tests/verify_refactor.php ---

<?php

/**
 * INTEGRATION TEST: ATTACK & WAR FLOW
 * Verifies the full chain: Controller Input -> Service -> Repository -> DB -> Events.
 * Ensuring DTOs are handled correctly throughout the lifecycle.
 */

if (php_sapi_name() !== 'cli') {
    die('Access Denied: CLI only.');
}

require __DIR__ . '/../../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();
} catch (\Exception $e) {
    // .env optional for tests if env vars set
}

use App\Core\ContainerFactory;
use App\Models\Services\AttackService;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\WarRepository;
use App\Models\Repositories\AllianceRoleRepository;

try {
    // 1. Setup Container
    $container = ContainerFactory::createContainer();
    $db = $container->get(PDO::class);
    
    // Resolve Services
    $attackService = $container->get(AttackService::class);
    $userRepo = $container->get(UserRepository::class);
    $resRepo = $container->get(ResourceRepository::class);
    $statsRepo = $container->get(StatsRepository::class);
    $structRepo = $container->get(StructureRepository::class);
    $allianceRepo = $container->get(AllianceRepository::class);
    $warRepo = $container->get(WarRepository::class);
    $roleRepo = $container->get(AllianceRoleRepository::class);

    // 2. Begin Transaction (Test Isolation)
    $db->beginTransaction(); 

    // 3. Seed Data
    $seed = bin2hex(random_bytes(2)); // 4 chars
    
    // Create Users
    $attackerId = $userRepo->createUser("atk_{$seed}@test.com", "Atk_{$seed}", 'hash');
    $defenderId = $userRepo->createUser("def_{$seed}@test.com", "Def_{$seed}", 'hash');
    
    // Init User State
    foreach ([$attackerId, $defenderId] as $uid) {
        $resRepo->createDefaults($uid);
        $statsRepo->createDefaults($uid);
        $structRepo->createDefaults($uid);
    }

    // Create Alliances
    $allyA = $allianceRepo->create("EmpireA_{$seed}", "A{$seed}", $attackerId);
    $allyB = $allianceRepo->create("EmpireB_{$seed}", "B{$seed}", $defenderId);
    
    // Assign Roles
    $roleA = $roleRepo->create($allyA, 'Member', 1, []);
    $roleB = $roleRepo->create($allyB, 'Member', 1, []);
    $userRepo->setAlliance($attackerId, $allyA, $roleA);
    $userRepo->setAlliance($defenderId, $allyB, $roleB);

    // Declare War (Unit Goal)
    $warId = $warRepo->createWar("War_{$seed}", $allyA, $allyB, 'Testing', 'units_killed', 1000);

    // Arm Attacker (Overwhelming Force)
    $resRepo->updateBattleAttacker($attackerId, 1000000, 1000); 
    $statsRepo->updateAttackTurns($attackerId, 10);

    // Arm Defender (Weak)
    $resRepo->updateBattleDefender($defenderId, 1000000, 50);

    // 4. Execute Logic
    echo "   [EXEC] Launching Attack (Attacker: $attackerId vs Defender: $defenderId)...\n";
    $targetUser = $userRepo->findById($defenderId);
    $response = $attackService->conductAttack($attackerId, $targetUser->characterName, 'plunder');

    // 5. Assertions
    if (!$response->isSuccess()) {
        throw new Exception("Attack Failed unexpectedly: " . $response->message);
    }

    // Check Battle Report Generation
    $stmt = $db->prepare("SELECT id, attack_result FROM battle_reports WHERE attacker_id = ? AND defender_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$attackerId, $defenderId]);
    $report = $stmt->fetch();

    if (!$report) throw new Exception("FAIL: Battle Report not created.");
    if ($report['attack_result'] !== 'victory') throw new Exception("FAIL: Expected victory, got {$report['attack_result']}");

    // Check Notification Dispatch (Event Listener)
    $stmt = $db->prepare("SELECT id FROM notifications WHERE user_id = ?");
    $stmt->execute([$defenderId]);
    if (!$stmt->fetch()) throw new Exception("FAIL: Defender notification not dispatched.");

    // Check War Log (Event Listener)
    $stmt = $db->prepare("SELECT id FROM war_battle_logs WHERE war_id = ? AND battle_report_id = ?");
    $stmt->execute([$warId, $report['id']]);
    if (!$stmt->fetch()) throw new Exception("FAIL: War Battle Log not created.");

    echo "   \033[32m[PASS] Full War Integration Cycle verified.\033[0m\n";

} catch (Throwable $e) {
    echo "   \033[31m[FAIL] " . $e->getMessage() . "\033[0m\n";
    echo "   Trace: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
} finally {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
}