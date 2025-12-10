--- START OF FILE tests/BattleSimulationTest.php ---

<?php

/**
 * FUNCTIONAL TEST: BATTLE MECHANICS
 * Verifies:
 * 1. Read-only Entities are used correctly.
 * 2. Resource logic (Loot/Losses) applies correctly.
 * 3. Stats logic (XP/Turns) updates correctly.
 */

if (php_sapi_name() !== 'cli') {
    die('Access Denied: CLI only.');
}

require __DIR__ . '/../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (\Exception $e) {}

use App\Core\ContainerFactory;
use App\Models\Services\AttackService;
use App\Models\Repositories\{UserRepository, ResourceRepository, StatsRepository, StructureRepository, BattleRepository};

try {
    $container = ContainerFactory::createContainer();
    $db = $container->get(PDO::class);
    $attackService = $container->get(AttackService::class);
    $userRepo = $container->get(UserRepository::class);
    $resRepo = $container->get(ResourceRepository::class);
    $statsRepo = $container->get(StatsRepository::class);
    $structRepo = $container->get(StructureRepository::class);
    $battleRepo = $container->get(BattleRepository::class);

    $db->beginTransaction();

    $seed = bin2hex(random_bytes(3));
    $atkId = $userRepo->createUser("sim_atk_{$seed}@test.com", "S_Atk_{$seed}", 'hash');
    $defId = $userRepo->createUser("sim_def_{$seed}@test.com", "S_Def_{$seed}", 'hash');

    // Init
    foreach ([$atkId, $defId] as $uid) {
        $resRepo->createDefaults($uid);
        $statsRepo->createDefaults($uid);
        $structRepo->createDefaults($uid);
    }

    // Config: Attacker wins
    $resRepo->updateBattleAttacker($atkId, 100000, 1000); // 1000 Soldiers
    $statsRepo->updateAttackTurns($atkId, 50);
    $resRepo->updateBattleDefender($defId, 5000000, 100); // 5M Lootable, 100 Guards

    // Run
    $targetUser = $userRepo->findById($defId);
    $response = $attackService->conductAttack($atkId, $targetUser->characterName, 'plunder');

    // Assertions
    if (!$response->isSuccess()) throw new Exception("Service returned failure: " . $response->message);

    $atkRes = $resRepo->findByUserId($atkId);
    $defRes = $resRepo->findByUserId($defId);
    $atkStats = $statsRepo->findByUserId($atkId);

    // 1. Check Loot
    if ($atkRes->credits <= 100000) throw new Exception("FAIL: Attacker failed to gain credits.");
    if ($defRes->credits >= 5000000) throw new Exception("FAIL: Defender failed to lose credits.");

    // 2. Check Turns
    if ($atkStats->attack_turns >= 50) throw new Exception("FAIL: Attack turns not deducted.");

    // 3. Check Persistence
    $reports = $battleRepo->findReportsByAttackerId($atkId);
    if (empty($reports)) throw new Exception("FAIL: Battle Report not saved.");

    // 4. Check DTO Integrity (Reflection)
    $reportClass = new ReflectionClass($reports[0]);
    if (!$reportClass->isReadOnly()) throw new Exception("FAIL: BattleReport Entity is not readonly.");

    echo "   \033[32m[PASS] Battle Simulation Complete. Mechanics Verified.\033[0m\n";

} catch (Throwable $e) {
    echo "   \033[31m[FAIL] " . $e->getMessage() . "\033[0m\n";
    exit(1);
} finally {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
}