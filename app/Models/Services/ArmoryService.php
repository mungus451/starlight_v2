<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\ArmoryRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository; 
use PDO;
use Throwable;

/**
 * Handles all business logic for the Armory (Manufacturing & Equipping).
 * * Fixed: Added nested transaction support to prevent test failures.
 */
class ArmoryService
{
    private PDO $db;
    private Config $config;
    
    private ArmoryRepository $armoryRepo;
    private ResourceRepository $resourceRepo;
    private StructureRepository $structureRepo;
    private StatsRepository $statsRepo; 
    
    private array $armoryConfig;

    public function __construct(
        PDO $db,
        Config $config,
        ArmoryRepository $armoryRepo,
        ResourceRepository $resourceRepo,
        StructureRepository $structureRepo,
        StatsRepository $statsRepo
    ) {
        $this->db = $db;
        $this->config = $config;
        
        $this->armoryRepo = $armoryRepo;
        $this->resourceRepo = $resourceRepo;
        $this->structureRepo = $structureRepo;
        $this->statsRepo = $statsRepo;
        
        $this->armoryConfig = $this->config->get('armory_items', []);
    }

    /**
     * Gets all data needed to render the full Armory UI.
     */
    public function getArmoryData(int $userId): array
    {
        // 1. Fetch Raw Data
        $userResources = $this->resourceRepo->findByUserId($userId);
        $userStructures = $this->structureRepo->findByUserId($userId);
        $userStats = $this->statsRepo->findByUserId($userId);
        $inventory = $this->armoryRepo->getInventory($userId);
        $loadouts = $this->armoryRepo->getUnitLoadouts($userId);

        // 2. Calculate Discounts
        $discountConfig = $this->config->get('game_balance.armory', []);
        $charisma = $userStats->charisma_points ?? 0;
        $rate = $discountConfig['discount_per_charisma'] ?? 0.01;
        $cap = $discountConfig['max_discount'] ?? 0.75;
        $discountPercent = min($charisma * $rate, $cap);

        // 3. Build Item Lookup Map
        $itemLookup = [];
        foreach ($this->armoryConfig as $unitData) {
            foreach ($unitData['categories'] as $categoryData) {
                foreach ($categoryData['items'] as $itemKey => $item) {
                    $itemLookup[$itemKey] = $item['name'];
                }
            }
        }

        // 4. Prepare Tiered Manufacturing Data (Raw State)
        $manufacturingData = [];
        foreach ($this->armoryConfig as $unitKey => $unitData) {
            $tieredRaw = $this->organizeByTier($unitData);
            
            $enrichedTiers = [];
            foreach ($tieredRaw as $tier => $items) {
                foreach ($items as $item) {
                    $enrichedTiers[$tier][] = $this->enrichItemData(
                        $item,
                        $inventory,
                        $userStructures->armory_level,
                        $discountPercent,
                        $itemLookup
                    );
                }
            }
            $manufacturingData[$unitKey] = $enrichedTiers;
        }

        return [
            'userResources' => $userResources,
            'userStructures' => $userStructures,
            'userStats' => $userStats,
            'armoryConfig' => $this->armoryConfig,
            'manufacturingData' => $manufacturingData,
            'inventory' => $inventory,
            'loadouts' => $loadouts,
            'itemLookup' => $itemLookup,
            'discountConfig' => $discountConfig,
            'discountPercent' => $discountPercent,
            'hasDiscount' => $discountPercent > 0
        ];
    }

    /**
     * Processes a batch of manufacturing orders in a single transaction.
     *
     * @param int $userId
     * @param array $items Array of ['item_key' => string, 'quantity' => int]
     * @return ServiceResponse
     */
    public function processBatchManufacture(int $userId, array $items): ServiceResponse
    {
        if (empty($items)) {
            return ServiceResponse::error('No items selected.');
        }

        // 1. Load User Data
        $userResources = $this->resourceRepo->findByUserId($userId);
        $userStructures = $this->structureRepo->findByUserId($userId);
        $userStats = $this->statsRepo->findByUserId($userId);
        $inventory = $this->armoryRepo->getInventory($userId);
        
        // Discount
        $discountSettings = $this->config->get('game_balance.armory', []);
        $rate = $discountSettings['discount_per_charisma'] ?? 0.01;
        $cap = $discountSettings['max_discount'] ?? 0.75;
        $discountPercent = min($userStats->charisma_points * $rate, $cap);

        // 2. Validate & Calculate Logic
        $totalCost = 0;
        $totalCrystalCost = 0;
        $totalDarkMatterCost = 0;
        $simulatedInventory = $inventory; // Use to track consumption within batch
        $opsToPerform = []; // ['item_key' => qty]

        // Flatten duplicates
        $mergedItems = [];
        foreach ($items as $req) {
            $key = $req['item_key'];
            $qty = (int)$req['quantity'];
            if ($qty <= 0) continue;
            if (!isset($mergedItems[$key])) $mergedItems[$key] = 0;
            $mergedItems[$key] += $qty;
        }

        if (empty($mergedItems)) {
            return ServiceResponse::error('No valid quantities provided.');
        }

        foreach ($mergedItems as $itemKey => $quantity) {
            $item = $this->findItemByKey($itemKey);
            if (!$item) {
                return ServiceResponse::error("Invalid item key: $itemKey");
            }

            // Level Req
            if ($userStructures->armory_level < ($item['armory_level_req'] ?? 0)) {
                return ServiceResponse::error("Armory level too low for {$item['name']}.");
            }

            // Cost (Credits)
            $baseCost = $item['cost_credits'];
            $effectiveUnitCost = (int)floor($baseCost * (1 - $discountPercent));
            $totalCost += ($effectiveUnitCost * $quantity);

            // Cost (Rare Resources) - No discount on materials
            $totalCrystalCost += ($item['cost_crystals'] ?? 0) * $quantity;
            $totalDarkMatterCost += ($item['cost_dark_matter'] ?? 0) * $quantity;

            // Prereqs
            $prereqKey = $item['requires'] ?? null;
            if ($prereqKey) {
                $currentStock = $simulatedInventory[$prereqKey] ?? 0;
                if ($currentStock < $quantity) {
                    $pItem = $this->findItemByKey($prereqKey);
                    $pName = $pItem['name'] ?? $prereqKey;
                    return ServiceResponse::error("Insufficient {$pName} for {$item['name']} batch. Need {$quantity}, have {$currentStock} available/remaining.");
                }
                $simulatedInventory[$prereqKey] -= $quantity;
            }
            
            $opsToPerform[$itemKey] = $quantity;
        }

        // 3. Check Resources
        if ($userResources->credits < $totalCost) {
            return ServiceResponse::error('Insufficient credits for batch. Total cost: ' . number_format($totalCost));
        }
        if ($userResources->naquadah_crystals < $totalCrystalCost) {
            return ServiceResponse::error('Insufficient Naquadah Crystals. Need: ' . number_format($totalCrystalCost));
        }
        if ($userResources->dark_matter < $totalDarkMatterCost) {
            return ServiceResponse::error('Insufficient Dark Matter. Need: ' . number_format($totalDarkMatterCost));
        }

        // 4. Execute Transaction
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }

        try {
            // Deduct All Resources
            $this->resourceRepo->updateResources(
                $userId, 
                -$totalCost, 
                -$totalCrystalCost, 
                -$totalDarkMatterCost
            );
            
            // Process Items
            foreach ($opsToPerform as $key => $qty) {
                 $item = $this->findItemByKey($key);
                 // Deduct Prereq
                 if (isset($item['requires'])) {
                     $this->armoryRepo->updateItemQuantity($userId, $item['requires'], -$qty);
                 }
                 // Add Item
                 $this->armoryRepo->updateItemQuantity($userId, $key, $qty);
            }
            
            $this->db->commit();
            return ServiceResponse::success("Batch manufacturing successful!", [
                'new_credits' => $userResources->credits - $totalCost,
                'new_crystals' => $userResources->naquadah_crystals - $totalCrystalCost,
                'new_dark_matter' => $userResources->dark_matter - $totalDarkMatterCost
            ]);

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Batch Armory Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred.');
        }
    }

    /**
     * Attempts to manufacture (or upgrade) a specific quantity of an item.
     * Returns updated resource and inventory state for immediate UI update.
     *
     * @param int $userId
     * @param string $itemKey
     * @param int $quantity
     * @return ServiceResponse Data: ['new_credits', 'new_owned', 'item_key']
     */
    public function manufactureItem(int $userId, string $itemKey, int $quantity): ServiceResponse
    {
        // 1. Validation (Input)
        if ($quantity <= 0) {
            return ServiceResponse::error('Quantity must be a positive number.');
        }

        $item = $this->findItemByKey($itemKey);
        if (is_null($item)) {
            return ServiceResponse::error('Invalid item selected.');
        }

        // 2. Get User Data
        $userResources = $this->resourceRepo->findByUserId($userId);
        $userStructures = $this->structureRepo->findByUserId($userId);
        $userStats = $this->statsRepo->findByUserId($userId);
        $inventory = $this->armoryRepo->getInventory($userId);

        // 3. Check Prerequisites
        $levelReq = $item['armory_level_req'] ?? 0;
        if ($userStructures->armory_level < $levelReq) {
            return ServiceResponse::error("You must have an Armory (Level {$levelReq}) to manufacture this item.");
        }

        // Calculate Cost
        $baseCost = $item['cost_credits'];
        $discountSettings = $this->config->get('game_balance.armory', []);
        $rate = $discountSettings['discount_per_charisma'] ?? 0.01;
        $cap = $discountSettings['max_discount'] ?? 0.75;
        $discountPercent = min($userStats->charisma_points * $rate, $cap);
        $effectiveUnitCost = (int)floor($baseCost * (1 - $discountPercent));
        
        $totalCost = $effectiveUnitCost * $quantity;
        $totalCrystalCost = ($item['cost_crystals'] ?? 0) * $quantity;
        $totalDarkMatterCost = ($item['cost_dark_matter'] ?? 0) * $quantity;
        
        if ($userResources->credits < $totalCost) {
            return ServiceResponse::error('You do not have enough credits.');
        }
        if ($userResources->naquadah_crystals < $totalCrystalCost) {
            return ServiceResponse::error('You do not have enough Naquadah Crystals.');
        }
        if ($userResources->dark_matter < $totalDarkMatterCost) {
            return ServiceResponse::error('You do not have enough Dark Matter.');
        }

        $prereqKey = $item['requires'] ?? null;
        if ($prereqKey) {
            $prereqInStock = $inventory[$prereqKey] ?? 0;
            if ($prereqInStock < $quantity) {
                $prereqItem = $this->findItemByKey($prereqKey);
                $pName = $prereqItem['name'] ?? $prereqKey;
                return ServiceResponse::error("You do not have enough {$pName}. You need {$quantity} and have {$prereqInStock}.");
            }
        }
        
        // 4. Execute Transaction (Smart Handling for Tests)
        $transactionStartedByMe = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $transactionStartedByMe = true;
        }

        $newCredits = 0;
        $newCrystals = 0;
        $newDarkMatter = 0;
        $newOwned = 0;

        try {
            // Deduct All Resources
            $newCredits = $userResources->credits - $totalCost;
            $newCrystals = $userResources->naquadah_crystals - $totalCrystalCost;
            $newDarkMatter = $userResources->dark_matter - $totalDarkMatterCost;

            $this->resourceRepo->updateResources(
                $userId, 
                -$totalCost, 
                -$totalCrystalCost, 
                -$totalDarkMatterCost
            );

            // Deduct Prerequisite Item
            if ($prereqKey) {
                $this->armoryRepo->updateItemQuantity($userId, $prereqKey, -$quantity);
            }

            // Add New Item
            $this->armoryRepo->updateItemQuantity($userId, $itemKey, +$quantity);
            
            // Commit only if we started it
            if ($transactionStartedByMe) {
                $this->db->commit();
            }
            
            // Calculate new owned count locally to save a query
            $currentOwned = $inventory[$itemKey] ?? 0;
            $newOwned = $currentOwned + $quantity;

        } catch (Throwable $e) {
            // Rollback only if we started it
            if ($transactionStartedByMe && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            // If caller started it, we re-throw or handle gracefully.
            // For logic consistency, we return error here, caller will decide what to do with their transaction.
            error_log('Armory Manufacture Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred during manufacturing.');
        }

        // Build Success Message
        $msg = "Successfully manufactured {$quantity}x " . $item['name'] . ".";
        if ($discountPercent > 0) {
            $saved = ($baseCost * $quantity) - $totalCost;
            $msg .= " (Charisma saved you " . number_format($saved) . " credits)";
        }

        return ServiceResponse::success($msg, [
            'new_credits' => $newCredits,
            'new_crystals' => $newCrystals,
            'new_dark_matter' => $newDarkMatter,
            'new_owned' => $newOwned,
            'item_key' => $itemKey
        ]);
    }

    /**
     * Equips an item to a unit's loadout slot.
     */
    public function equipItem(int $userId, string $unitKey, string $categoryKey, string $itemKey): ServiceResponse
    {
        // 1. Validate inputs
        if (empty($unitKey) || empty($categoryKey)) {
            return ServiceResponse::error('Invalid loadout data provided.');
        }
        
        // 2. Handle Unequip
        if (empty($itemKey)) {
            $this->armoryRepo->clearLoadoutSlot($userId, $unitKey, $categoryKey); 
            $title = $this->armoryConfig[$unitKey]['title'] ?? $unitKey;
            return ServiceResponse::success("Loadout slot cleared for {$title}.");
        }

        // 3. Validate Item
        $item = $this->findItemByKey($itemKey);
        $isValid = isset($this->armoryConfig[$unitKey]['categories'][$categoryKey]['items'][$itemKey]);

        if (!$item || !$isValid) {
            return ServiceResponse::error('That item cannot be equipped to that slot.');
        }

        // 4. Execute Update
        $this->armoryRepo->setLoadout($userId, $unitKey, $categoryKey, $itemKey);
        $title = $this->armoryConfig[$unitKey]['title'] ?? $unitKey;
        
        return ServiceResponse::success("{$item['name']} is now the standard issue for all {$title}.");
    }

    // --- Helpers (Logic only) ---

    public function getAggregateBonus(int $userId, string $unitKey, string $statType, int $unitCount): int
    {
        if ($unitCount <= 0) return 0;

        $loadouts = $this->armoryRepo->getUnitLoadouts($userId);
        $inventory = $this->armoryRepo->getInventory($userId);
        $categories = $this->armoryConfig[$unitKey]['categories'] ?? [];
        
        if (empty($categories)) return 0;

        $totalBonus = 0;

        foreach ($categories as $categoryKey => $categoryData) {
            $equippedItemKey = $loadouts[$unitKey][$categoryKey] ?? null;
            if (!$equippedItemKey) continue; 

            $item = $this->findItemByKey($equippedItemKey);
            $itemBonus = $item[$statType] ?? 0;
            if (!$item || $itemBonus === 0) continue;

            $itemInStock = $inventory[$equippedItemKey] ?? 0;
            if ($itemInStock === 0) continue;

            $eligibleUnits = min($unitCount, $itemInStock);
            $totalBonus += ($eligibleUnits * $itemBonus);
        }

        return $totalBonus;
    }

    private function enrichItemData(array $item, array $inventory, int $armoryLevel, float $discountPercent, array $lookup): array
    {
        $itemKey = $item['item_key'];
        
        $isTier1 = !isset($item['requires']);
        $prereqKey = $item['requires'] ?? null;
        $prereqName = $prereqKey ? ($lookup[$prereqKey] ?? 'Unknown Item') : null;
        $prereqOwned = $prereqKey ? (int)($inventory[$prereqKey] ?? 0) : 0;
        
        $baseCost = $item['cost_credits'];
        $effectiveCost = (int)floor($baseCost * (1 - $discountPercent));

        // --- NEW: Add special resource costs (no discounts apply) ---
        $crystalCost = $item['cost_crystals'] ?? 0;
        $darkMatterCost = $item['cost_dark_matter'] ?? 0;
        
        $reqLevel = $item['armory_level_req'] ?? 0;
        $hasLevel = $armoryLevel >= $reqLevel;
        $hasPrereq = $isTier1 || $prereqOwned > 0;
        $canManufacture = $hasLevel && $hasPrereq;
        
        // Pass pure data to Controller/Presenter
        $item['is_tier_1'] = $isTier1;
        $item['prereq_key'] = $prereqKey;
        $item['prereq_name'] = $prereqName;
        $item['prereq_owned'] = $prereqOwned;
        $item['current_owned'] = (int)($inventory[$itemKey] ?? 0);
        $item['base_cost'] = $baseCost;
        $item['effective_cost'] = $effectiveCost;
        $item['cost_crystals'] = $crystalCost;
        $item['cost_crystals_formatted'] = number_format($crystalCost);
        $item['cost_dark_matter'] = $darkMatterCost;
        $item['cost_dark_matter_formatted'] = number_format($darkMatterCost);
        $item['armory_level_req'] = $reqLevel;
        $item['has_level'] = $hasLevel;
        $item['can_manufacture'] = $canManufacture;
        
        return $item;
    }

    private function organizeByTier(array $unitData): array
    {
        $allItems = [];
        foreach ($unitData['categories'] as $catKey => $catData) {
            foreach ($catData['items'] as $itemKey => $item) {
                $item['item_key'] = $itemKey;
                $item['slot_name'] = $catData['title'];
                $item['category_key'] = $catKey; 
                $allItems[$itemKey] = $item;
            }
        }
        
        error_log("DEBUG: Armory - Unit: " . ($unitData['unit'] ?? 'unknown') . " - Total Items found: " . count($allItems));

        $tieredItems = [];
        foreach ($allItems as $key => $item) {
            $tier = $this->calculateItemTier($key, $allItems);
            $tieredItems[$tier][] = $item;
        }

        ksort($tieredItems);
        return $tieredItems;
    }

    private function calculateItemTier(string $itemKey, array $allItems, int $depth = 0): int
    {
        if ($depth > 10) return 99; 
        $item = $allItems[$itemKey] ?? null;
        if (!$item) return 1; 
        if (empty($item['requires'])) return 1;
        return 1 + $this->calculateItemTier($item['requires'], $allItems, $depth + 1);
    }

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