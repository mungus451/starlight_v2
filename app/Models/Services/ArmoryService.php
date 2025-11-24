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
 * * V2: Now includes View Model Preparation to remove logic from templates.
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
        
        $this->armoryConfig = $this->config->get('armory_items', []);
    }

    /**
     * Gets all data needed to render the full Armory UI.
     * Prepares a "dumb" view model array to strictly separate concerns.
     *
     * @param int $userId
     * @return array
     */
    public function getArmoryData(int $userId): array
    {
        // 1. Fetch Raw Data
        $userResources = $this->resourceRepo->findByUserId($userId);
        $userStructures = $this->structureRepo->findByUserId($userId);
        $userStats = $this->statsRepo->findByUserId($userId);
        $inventory = $this->armoryRepo->getInventory($userId);
        $loadouts = $this->armoryRepo->getUnitLoadouts($userId);

        // 2. Calculate Discounts (Business Logic)
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

        // 4. Prepare Tiered Manufacturing Data (The "View Model" logic)
        $manufacturingData = [];
        foreach ($this->armoryConfig as $unitKey => $unitData) {
            // Get flat tiered list
            $tieredRaw = $this->organizeByTier($unitData);
            
            // Enrich every item with display logic status
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
            // Raw Entities (Safe for view to read properties)
            'userResources' => $userResources,
            'userStructures' => $userStructures,
            'userStats' => $userStats,
            
            // Logic-Free Arrays
            'armoryConfig' => $this->armoryConfig, // Structure for Loadouts
            'manufacturingData' => $manufacturingData, // Pre-calculated for View
            'inventory' => $inventory,
            'loadouts' => $loadouts,
            
            // Display Helpers
            'discountPercent' => $discountPercent,
            'hasDiscount' => $discountPercent > 0
        ];
    }

    /**
     * Enriches a raw item array with calculated statuses, costs, and flags.
     * This removes all `if()` logic from the View.
     */
    private function enrichItemData(array $item, array $inventory, int $armoryLevel, float $discountPercent, array $lookup): array
    {
        $itemKey = $item['item_key'];
        
        // Prerequisites
        $isTier1 = !isset($item['requires']);
        $prereqKey = $item['requires'] ?? null;
        $prereqName = $prereqKey ? ($lookup[$prereqKey] ?? 'Unknown Item') : null;
        $prereqOwned = $prereqKey ? (int)($inventory[$prereqKey] ?? 0) : 0;
        
        // Costs
        $baseCost = $item['cost'];
        $effectiveCost = (int)floor($baseCost * (1 - $discountPercent));
        
        // Status Checks
        $reqLevel = $item['armory_level_req'] ?? 0;
        $hasLevel = $armoryLevel >= $reqLevel;
        $hasPrereq = $isTier1 || $prereqOwned > 0;
        $canManufacture = $hasLevel && $hasPrereq;
        
        // Add enriched fields
        $item['is_tier_1'] = $isTier1;
        $item['prereq_key'] = $prereqKey;
        $item['prereq_name'] = $prereqName;
        $item['prereq_owned'] = $prereqOwned;
        $item['current_owned'] = (int)($inventory[$itemKey] ?? 0);
        
        $item['base_cost'] = $baseCost;
        $item['effective_cost'] = $effectiveCost;
        $item['armory_level_req'] = $reqLevel;
        
        $item['can_manufacture'] = $canManufacture;
        $item['level_status_class'] = $hasLevel ? 'status-ok' : 'status-bad';
        $item['manufacture_btn_text'] = $isTier1 ? 'Manufacture' : 'Upgrade';
        
        // Stat Badges (Pre-formatted for simple loop in view)
        $badges = [];
        if (isset($item['attack'])) $badges[] = ['type' => 'attack', 'label' => "+{$item['attack']} Atk"];
        if (isset($item['defense'])) $badges[] = ['type' => 'defense', 'label' => "+{$item['defense']} Def"];
        if (isset($item['credit_bonus'])) $badges[] = ['type' => 'defense', 'label' => "+{$item['credit_bonus']} Cr"]; // Re-using defense style for gold
        $item['stat_badges'] = $badges;

        return $item;
    }

    /**
     * Reorganizes a Unit's config from Categories -> Tiers.
     */
    private function organizeByTier(array $unitData): array
    {
        $allItems = [];
        
        // 1. Flatten items and attach Category Name (Slot Name)
        foreach ($unitData['categories'] as $catKey => $catData) {
            foreach ($catData['items'] as $itemKey => $item) {
                $item['item_key'] = $itemKey;
                $item['slot_name'] = $catData['title'];
                $item['category_key'] = $catKey; 
                $allItems[$itemKey] = $item;
            }
        }

        // 2. Calculate Tier for every item
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

    // --- Transactional Actions (Unchanged logic, kept for completeness) ---

    public function manufactureItem(int $userId, string $itemKey, int $quantity): bool
    {
        if ($quantity <= 0) {
            $this->session->setFlash('error', 'Quantity must be a positive number.');
            return false;
        }

        $item = $this->findItemByKey($itemKey);
        if (is_null($item)) {
            $this->session->setFlash('error', 'Invalid item selected.');
            return false;
        }

        $userResources = $this->resourceRepo->findByUserId($userId);
        $userStructures = $this->structureRepo->findByUserId($userId);
        $userStats = $this->statsRepo->findByUserId($userId);
        $inventory = $this->armoryRepo->getInventory($userId);

        $levelReq = $item['armory_level_req'] ?? 0;
        if ($userStructures->armory_level < $levelReq) {
            $this->session->setFlash('error', "You must have an Armory (Level {$levelReq}) to manufacture this item.");
            return false;
        }

        $baseCost = $item['cost'];
        $discountSettings = $this->config->get('game_balance.armory', []);
        $rate = $discountSettings['discount_per_charisma'] ?? 0.01;
        $cap = $discountSettings['max_discount'] ?? 0.75;
        $discountPercent = min($userStats->charisma_points * $rate, $cap);
        $effectiveUnitCost = (int)floor($baseCost * (1 - $discountPercent));
        
        $totalCost = $effectiveUnitCost * $quantity;
        
        if ($userResources->credits < $totalCost) {
            $this->session->setFlash('error', 'You do not have enough credits.');
            return false;
        }

        $prereqKey = $item['requires'] ?? null;
        if ($prereqKey) {
            $prereqInStock = $inventory[$prereqKey] ?? 0;
            if ($prereqInStock < $quantity) {
                $prereqItem = $this->findItemByKey($prereqKey);
                $this->session->setFlash('error', "You do not have enough " . ($prereqItem['name'] ?? $prereqKey) . ". You need {$quantity} and have {$prereqInStock}.");
                return false;
            }
        }
        
        $this->db->beginTransaction();
        try {
            $this->resourceRepo->updateCredits($userId, $userResources->credits - $totalCost);
            if ($prereqKey) {
                $this->armoryRepo->updateItemQuantity($userId, $prereqKey, -$quantity);
            }
            $this->armoryRepo->updateItemQuantity($userId, $itemKey, +$quantity);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
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

    public function equipItem(int $userId, string $unitKey, string $categoryKey, string $itemKey): bool
    {
        if (empty($unitKey) || empty($categoryKey)) {
            $this->session->setFlash('error', 'Invalid loadout data provided.');
            return false;
        }
        
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

        $this->armoryRepo->setLoadout($userId, $unitKey, $categoryKey, $itemKey);
        $this->session->setFlash('success', $item['name'] . " is now the standard issue for all " . $this->armoryConfig[$unitKey]['title'] . ".");
        return true;
    }

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