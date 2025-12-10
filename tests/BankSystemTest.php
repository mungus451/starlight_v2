<?php

declare(strict_types=1);

/**
 * BANK SYSTEM INTEGRATION TEST
 *
 * Verifies:
 * - Deposit Logic (Limits, Charges, Balance Updates)
 * - Withdraw Logic (Balance Updates)
 * - Transfer Logic (P2P Transactions)
 * - Charge Regeneration (Time-based logic)
 *
 * Usage: docker exec starlight_app php tests/BankSystemTest.php
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
use App\Core\Config;
use App\Models\Services\BankService;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;

echo "\n" . str_repeat("=", 60) . "\n";
echo "   BANKING SYSTEM INTEGRATION TEST\n";
echo str_repeat("=", 60) . "\n";

try {
    // 1. Boot Container
    $container = ContainerFactory::createContainer();
    $db = $container->get(PDO::class);
    $bankService = $container->get(BankService::class);
    $userRepo = $container->get(UserRepository::class);
    $resRepo = $container->get(ResourceRepository::class);
    $statsRepo = $container->get(StatsRepository::class);
    $config = $container->get(Config::class);

    // 2. Start Transaction
    $db->beginTransaction();

    // 3. Setup Test Data
    $seed = (string)microtime(true);
    
    // User A: "Tycoon" (Rich)
    $u1Name = "Tycoon_{$seed}";
    $u1 = $userRepo->createUser("u1_{$seed}@test.com", $u1Name, 'hash');
    $resRepo->createDefaults($u1);
    $statsRepo->createDefaults($u1);
    // Give 100M Credits
    $resRepo->updateCredits($u1, 100000000); 

    // User B: "Pauper" (Poor)
    $u2Name = "Pauper_{$seed}";
    $u2 = $userRepo->createUser("u2_{$seed}@test.com", $u2Name, 'hash');
    $resRepo->createDefaults($u2);
    $statsRepo->createDefaults($u2);
    // Give 0 Credits
    $resRepo->updateCredits($u2, 0);

    echo "Created Test Users: [ID $u1] $u1Name (100M), [ID $u2] $u2Name (0)\n\n";

    // --- TEST 1: DEPOSIT LIMITS ---
    echo "1. Testing Deposit Limits...\n";
    
    // Attempt to deposit 90M (90%) -> Should Fail (Max 80%)
    $res = $bankService->deposit($u1, 90000000);
    if ($res->isSuccess()) throw new Exception("FAIL: Deposited > 80% successfully.");
    echo "   -> Passed: Blocked 90% deposit.\n";

    // Attempt to deposit 50M (50%) -> Should Success
    $res = $bankService->deposit($u1, 50000000);
    if (!$res->isSuccess()) throw new Exception("FAIL: Valid deposit blocked: " . $res->message);
    
    $r1 = $resRepo->findByUserId($u1);
    if ($r1->credits !== 50000000 || $r1->banked_credits !== 50000000) {
        throw new Exception("FAIL: Balances not updated correctly.");
    }
    
    $s1 = $statsRepo->findByUserId($u1);
    if ($s1->deposit_charges !== 3) { // Started with 4, used 1
        throw new Exception("FAIL: Deposit charge not deducted. Has: {$s1->deposit_charges}");
    }
    echo "   -> Passed: Valid deposit processed (Charges: 4->3).\n\n";

    // --- TEST 2: CHARGE CONSUMPTION ---
    echo "2. Testing Charge Consumption...\n";
    
    // Burn remaining 3 charges
    for ($i=0; $i<3; $i++) {
        $res = $bankService->deposit($u1, 100000); // Small deposits
        if (!$res->isSuccess()) throw new Exception("FAIL: Valid deposit failed on charge ".($i+1));
    }
    
    $s1 = $statsRepo->findByUserId($u1);
    if ($s1->deposit_charges !== 0) throw new Exception("FAIL: Charges not depleted.");
    
    // Try one more -> Should Fail
    $res = $bankService->deposit($u1, 100000);
    if ($res->isSuccess()) throw new Exception("FAIL: Deposited with 0 charges.");
    echo "   -> Passed: Blocked deposit with 0 charges.\n\n";

    // --- TEST 3: CHARGE REGENERATION ---
    echo "3. Testing Charge Regeneration...\n";
    
    // Manually backdate the last_deposit_at by 7 hours (Regen is every 6 hours)
    $sevenHoursAgo = (new DateTime())->modify('-7 hours')->format('Y-m-d H:i:s');
    
    // We have to bypass the Service/Repo abstraction to force this DB state for testing
    $stmt = $db->prepare("UPDATE user_stats SET last_deposit_at = ? WHERE user_id = ?");
    $stmt->execute([$sevenHoursAgo, $u1]);
    
    // Trigger logic via getBankData
    $bankData = $bankService->getBankData($u1);
    $regenStats = $bankData['stats'];
    
    if ($regenStats->deposit_charges !== 1) {
        throw new Exception("FAIL: Charge did not regenerate. Expected 1, got {$regenStats->deposit_charges}");
    }
    echo "   -> Passed: Regenerated 1 charge after 7 hours.\n\n";

    // --- TEST 4: WITHDRAWAL ---
    echo "4. Testing Withdrawal...\n";
    
    // User 1 Bank: ~50.3M (50M + 300k small deposits). Hand: ~49.7M
    // Withdraw 10M
    $res = $bankService->withdraw($u1, 10000000);
    if (!$res->isSuccess()) throw new Exception("FAIL: Withdraw failed.");
    
    $r1 = $resRepo->findByUserId($u1);
    // Hand should increase by 10M, Bank decrease by 10M
    if ($r1->banked_credits < 40000000) throw new Exception("FAIL: Bank balance wrong after withdraw.");
    
    // Try to withdraw more than in bank
    $res = $bankService->withdraw($u1, 999999999);
    if ($res->isSuccess()) throw new Exception("FAIL: Withdrew ghost credits.");
    echo "   -> Passed: Withdrawal logic verified.\n\n";

    // --- TEST 5: TRANSFER ---
    echo "5. Testing Transfers...\n";
    
    // User 1 sends 1M to User 2
    $u1Before = $resRepo->findByUserId($u1)->credits;
    $u2Before = $resRepo->findByUserId($u2)->credits;
    
    $res = $bankService->transfer($u1, $u2Name, 1000000);
    if (!$res->isSuccess()) throw new Exception("FAIL: Transfer failed: " . $res->message);
    
    $u1After = $resRepo->findByUserId($u1)->credits;
    $u2After = $resRepo->findByUserId($u2)->credits;
    
    if ($u1Before - $u1After !== 1000000) throw new Exception("FAIL: Sender not deducted correctly.");
    if ($u2After - $u2Before !== 1000000) throw new Exception("FAIL: Recipient not credited correctly.");
    
    // Fail Case: Self Transfer
    $res = $bankService->transfer($u1, $u1Name, 100);
    if ($res->isSuccess()) throw new Exception("FAIL: Allowed self-transfer.");
    
    // Fail Case: Insufficient Funds
    $res = $bankService->transfer($u2, $u1Name, 50000000); // Pauper only has 1M
    if ($res->isSuccess()) throw new Exception("FAIL: Allowed transfer without funds.");
    
    echo "   -> Passed: Transfer logic verified.\n\n";

    // --- CLEANUP ---
    $db->rollBack();
    echo "✅ TEST COMPLETE: Banking System Fully Verified.\n";

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "\n❌ TEST FAILED: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}