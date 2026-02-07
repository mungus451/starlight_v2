<?php

declare(strict_types=1);

/**
 * ARMORY EQUIP ALL TEST
 *
 * Verifies that EVERY single item in the configuration can be:
 * 1. Manufactured (given resources/level)
 * 2. Equipped (Service layer check)
 */

if (php_sapi_name() !== 'cli') {
    die('Access Denied: CLI only.');
}

require __DIR__ . '/../../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();
} catch (Throwable $e) {
    // skip
}

use App\Core\ContainerFactory;
use App\Models\Services\ArmoryService;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\ArmoryRepository;
use App\Core\Config;

echo "\n" . str_repeat("=", 60) . "\n";
echo "   ARMORY FULL ROSTER EQUIP TEST\n";
echo str_repeat("=", 60) . "\n";

try {
    $container = ContainerFactory::createContainer();
    $db = $container->get(PDO::class);
    $armoryService = $container->get(ArmoryService::class);
    $userRepo = $container->get(UserRepository::class);
    $resRepo = $container->get(ResourceRepository::class);
    $structRepo = $container->get(StructureRepository::class);
    $armoryRepo = $container->get(ArmoryRepository::class);
    $config = $container->get(Config::class);

    $db->beginTransaction();

    // 1. Setup User
    $seed = (string)microtime(true);
    $userId = $userRepo->createUser("equip_test_{$seed}@example.com", "EquipMaster", 'hash');
    $resRepo->createDefaults($userId);
    $container->get(StatsRepository::class)->createDefaults($userId); // Added missing stats init
    $structRepo->createDefaults($userId);

    // Give infinite resources and max level
    $resRepo->updateCredits($userId, 100_000_000_000); 
    $structRepo->updateStructureLevel($userId, 'armory_level', 100);

    // 2. Load Config Items
    $armoryConfig = $config->get('armory_items');
    $totalItems = 0;
    $passedItems = 0;

    foreach ($armoryConfig as $unitKey => $unitData) {
        echo "Testing Unit: " . $unitData['title'] . "\n";
        
        foreach ($unitData['categories'] as $catKey => $catData) {
            echo "  Category: " . $catData['title'] . "\n";
            
            // Sort items by prereq to manufacture in order
            $items = $catData['items'];
            // Simple sort: items without prereqs first
            // Actually, we can just brute force manufacture them in order if the array is defined in tier order
            
            foreach ($items as $itemKey => $itemDef) {
                $totalItems++;
                echo "    - Item: {$itemDef['name']} ($itemKey)... ";

                // A. Manufacture
                // If it has prereq, we need to ensure we own it. 
                // Since we iterate in order (Tier 1 -> Tier 5), earlier items should be owned.
                // But manufacturing consumes them! 
                // So we need to manufacture the prereq AGAIN if needed?
                // Or just manufacture the current item directly if we have infinite resources?
                // No, logic requires consumption.
                
                // Hack: Manufacture 10 of EVERYTHING in the chain before testing?
                // Better: Just loop manufacturing until we succeed.
                
                $manufactured = false;
                $attempts = 0;
                while (!$manufactured && $attempts < 5) {
                    $attempts++;
                    $res = $armoryService->manufactureItem($userId, $itemKey, 1);
                    if ($res->isSuccess()) {
                        $manufactured = true;
                    } else {
                        // Failed? Probably missing prereq.
                        // Check if error message mentions missing item.
                        $prereq = $itemDef['requires'] ?? null;
                        if ($prereq) {
                             // Make the prereq
                             $armoryService->manufactureItem($userId, $prereq, 5);
                        } else {
                            // If failed and no prereq... unknown error
                            throw new Exception("Failed to manufacture $itemKey: " . $res->message);
                        }
                    }
                }

                if (!$manufactured) {
                     throw new Exception("Could not manufacture $itemKey after attempts.");
                }

                // B. Equip
                $res = $armoryService->equipItem($userId, $unitKey, $catKey, $itemKey);
                
                if (!$res->isSuccess()) {
                    throw new Exception("FAILED TO EQUIP: " . $res->message);
                }

                // C. Verify DB
                $loadouts = $armoryRepo->getUnitLoadouts($userId);
                if (($loadouts[$unitKey][$catKey] ?? '') !== $itemKey) {
                    throw new Exception("DB Verification Failed. Expected $itemKey.");
                }

                echo "OK\n";
                $passedItems++;
            }
        }
    }

    $db->rollBack(); // Cleanup
    echo "\n✅ ALL ITEMS EQUIPPED SUCCESSFULLY ($passedItems / $totalItems)\n";

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "\n❌ TEST FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
