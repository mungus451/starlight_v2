<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\Session;
use App\Models\Repositories\ArmoryRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository; 
use PDO;
use Throwable;

/**
 * Handles all business logic for the Armory (Manufacturing & Equipping).
 * * Refactored for Strict Dependency Injection.
 */
class ArmoryService
{
    private PDO $db;
    private Session $session;
    private Config $config;
    
    private ArmoryRepository $armoryRepo;
    private ResourceRepository $resourceRepo;
    private StructureRepository $structureRepo;
    private StatsRepository $statsRepo; 
    
    private array $armoryConfig;

    /**
     * DI Constructor.
     *
     * @param PDO $db
     * @param Session $session
     * @param Config $config
     * @param ArmoryRepository $armoryRepo
     * @param ResourceRepository $resourceRepo
     * @param StructureRepository $structureRepo
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        PDO $db,
        Session $session,
        Config $config,
        ArmoryRepository $armoryRepo,
        ResourceRepository $resourceRepo,
        StructureRepository $structureRepo,
        StatsRepository $statsRepo
    ) {
        $this->db = $db;
        $this->session = $session;
        $this->config = $config;
        
        $this->armoryRepo = $armoryRepo;
        $this->resourceRepo = $resourceRepo;
        $this->structureRepo = $structureRepo;
        $this->statsRepo = $statsRepo;
        
        // Load the armory config immediately
        $this->armoryConfig = $this->config->get('armory_items', []);
    }

    /**
     * Gets all data needed to render the full Armory UI.
     *
     * @param int $userId
     * @return array
     */
    public function getArmoryData(int $userId): array
    {
        // Get all data in parallel
        $userResources = $this->resourceRepo->findByUserId($userId);
        $userStructures = $this->structureRepo->findByUserId($userId);
        $userStats = $this->statsRepo->findByUserId($userId);
        $inventory = $this->armoryRepo->getInventory($userId);
        $loadouts = $this->armoryRepo->getUnitLoadouts($userId);

        // Create the item lookup map
        $itemLookup = [];
        foreach ($this->armoryConfig as $unitData) {
            foreach ($unitData['categories'] as $categoryData) {
                foreach ($categoryData['items'] as $itemKey => $item) {
                    $itemLookup[$itemKey] = $item['name'];
                }
            }
        }
        
        // Get discount config
        $discountConfig = $this->config->get('game_balance.armory', []);

        return [
            'armoryConfig' => $this->armoryConfig,
            'userResources' => $userResources,
            'userStructures' => $userStructures,
            'userStats' => $userStats,
            'inventory' => $inventory,
            'loadouts' => $loadouts,
            'itemLookup' => $itemLookup,
            'discountConfig' => $discountConfig,
        ];
    }

    /**
     * Attempts to manufacture (or upgrade) a specific quantity of an item.
     *
     * @param int $userId
     * @param string $itemKey
     * @param int $quantity
     * @return bool True on success
     */
    public function manufactureItem(int $userId, string $itemKey, int $quantity): bool
    {
        // 1. Validation (Input)
        if ($quantity <= 0) {
            $this->session->setFlash('error', 'Quantity must be a positive number.');
            return false;
        }

        $item = $this->findItemByKey($itemKey);
        if (is_null($item)) {
            $this->session->setFlash('error', 'Invalid item selected.');
            return false;
        }

        // 2. Get User Data
        $userResources = $this->resourceRepo->findByUserId($userId);
        $userStructures = $this->structureRepo->findByUserId($userId);
        $userStats = $this->statsRepo->findByUserId($userId);
        $inventory = $this->armoryRepo->getInventory($userId);

        // 3. Check Prerequisites
        // Check 3a: Armory Level
        $levelReq = $item['armory_level_req'] ?? 0;
        if ($userStructures->armory_level < $levelReq) {
            $this->session->setFlash('error', "You must have an Armory (Level {$levelReq}) to manufacture this item.");
            return false;
        }

        // Calculate Discounted Cost
        $baseCost = $item['cost'];
        $discountSettings = $this->config->get('game_balance.armory', []);
        $rate = $discountSettings['discount_per_charisma'] ?? 0.01;
        $cap = $discountSettings['max_discount'] ?? 0.75;
        
        // Discount formula: Charisma * Rate, capped at Max
        $discountPercent = min($userStats->charisma_points * $rate, $cap);
        $effectiveUnitCost = (int)floor($baseCost * (1 - $discountPercent));
        
        // Check 3b: Total Cost
        $totalCost = $effectiveUnitCost * $quantity;
        
        if ($userResources->credits < $totalCost) {
            $this->session->setFlash('error', 'You do not have enough credits.');
            return false;
        }

        // Check 3c: Consumable Prerequisite Item (if not Tier 1)
        $prereqKey = $item['requires'] ?? null;
        if ($prereqKey) {
            $prereqInStock = $inventory[$prereqKey] ?? 0;
            if ($prereqInStock < $quantity) {
                $prereqItem = $this->findItemByKey($prereqKey);
                $this->session->setFlash('error', "You do not have enough " . ($prereqItem['name'] ?? $prereqKey) . ". You need {$quantity} and have {$prereqInStock}.");
                return false;
            }
        }
        
        // 4. Execute Transaction
        $this->db->beginTransaction();
        try {
            // 4a. Deduct Credits
            $this->resourceRepo->updateCredits($userId, $userResources->credits - $totalCost);

            // 4b. Deduct Prerequisite Item (if applicable)
            if ($prereqKey) {
                $this->armoryRepo->updateItemQuantity($userId, $prereqKey, -$quantity);
            }

            // 4c. Add New Item
            $this->armoryRepo->updateItemQuantity($userId, $itemKey, +$quantity);

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Armory Manufacture Error: ' . $e->getMessage());
            $this->session->setFlash('error', 'A database error occurred during manufacturing.');
            return false;
        }

        $msg = "Successfully manufactured {$quantity}x " . $item['name'] . ".";
        if ($discountPercent > 0) {
            $saved = ($baseCost * $quantity) - $totalCost;
            $msg .= " (Charisma saved you " . number_format($saved) . " credits)";
        }

        $this->session->setFlash('success', $msg);
        return true;
    }

    /**
     * Equips an item to a unit's loadout slot.
     *
     * @param int $userId
     * @param string $unitKey
     * @param string $categoryKey
     * @param string $itemKey
     * @return bool True on success
     */
    public function equipItem(int $userId, string $unitKey, string $categoryKey, string $itemKey): bool
    {
        // 1. Validate inputs
        if (empty($unitKey) || empty($categoryKey)) {
            $this->session->setFlash('error', 'Invalid loadout data provided.');
            return false;
        }
        
        // 2. Check that this is a valid assignment
        // Handle "None Equipped"
        if (empty($itemKey)) {
            $this->armoryRepo->clearLoadoutSlot($userId, $unitKey, $categoryKey); 
            $this->session->setFlash('success', "Loadout slot cleared for " . $this->armoryConfig[$unitKey]['title'] . ".");
            return true;
        }

        $item = $this->findItemByKey($itemKey);
        $isValid = isset($this->armoryConfig[$unitKey]['categories'][$categoryKey]['items'][$itemKey]);

        if (!$item || !$isValid) {
            $this->session->setFlash('error', 'That item cannot be equipped to that slot.');
            return false;
        }

        // 3. Execute Update
        $this->armoryRepo->setLoadout($userId, $unitKey, $categoryKey, $itemKey);
        $this->session->setFlash('success', $item['name'] . " is now the standard issue for all " . $this->armoryConfig[$unitKey]['title'] . ".");
        return true;
    }

    /**
     * Calculates the total stat bonus for a unit stack based on their loadout.
     *
     * @param int $userId
     * @param string $unitKey (e.g., 'soldier')
     * @param string $statType (e.g., 'attack' or 'defense')
     * @param int $unitCount (e.g., 1000 soldiers)
     * @return int The total aggregate bonus.
     */
    public function getAggregateBonus(int $userId, string $unitKey, string $statType, int $unitCount): int
    {
        if ($unitCount <= 0) {
            return 0;
        }

        // 1. Get all required data
        $loadouts = $this->armoryRepo->getUnitLoadouts($userId);
        $inventory = $this->armoryRepo->getInventory($userId);
        
        // 2. Get the categories for this unit
        $categories = $this->armoryConfig[$unitKey]['categories'] ?? [];
        if (empty($categories)) {
            return 0;
        }

        $totalBonus = 0;

        // 3. Loop through each category slot for the unit
        foreach ($categories as $categoryKey => $categoryData) {
            
            // 4. Find what item is equipped in this slot
            $equippedItemKey = $loadouts[$unitKey][$categoryKey] ?? null;
            if (!$equippedItemKey) {
                continue; 
            }

            // 5. Get the item's details and stat bonus
            $item = $this->findItemByKey($equippedItemKey);
            $itemBonus = $item[$statType] ?? 0;
            if (!$item || $itemBonus === 0) {
                continue;
            }

            // 6. Get the inventory count for this item
            $itemInStock = $inventory[$equippedItemKey] ?? 0;
            if ($itemInStock === 0) {
                continue;
            }

            // 7. Core logic: min(units, items_in_stock)
            $eligibleUnits = min($unitCount, $itemInStock);

            // 8. Add the bonus for those units
            $totalBonus += ($eligibleUnits * $itemBonus);
        }

        return $totalBonus;
    }

    /**
     * Helper function to find an item's data from the nested config array.
     */
    private function findItemByKey(string $itemKey): ?array
    {
        foreach ($this->armoryConfig as $unitData) {
            foreach ($unitData['categories'] as $categoryData) {
                if (isset($categoryData['items'][$itemKey])) {
                    return $categoryData['items'][$itemKey];
                }
            }
        }
        return null;
    }
}