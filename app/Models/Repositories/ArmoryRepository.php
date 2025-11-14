<?php

namespace App\Models\Repositories;

use PDO;

/**
 * Handles all database operations for the 'user_armory_inventory'
 * and 'user_unit_loadouts' tables.
 */
class ArmoryRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Gets a user's entire item inventory.
     *
     * @param int $userId
     * @return array An associative array of [item_key => quantity]
     */
    public function getInventory(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT item_key, quantity FROM user_armory_inventory WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
        
        // Returns a simple [ 'pulse_rifle' => 100, 'railgun' => 50 ] array
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Gets a user's currently equipped loadouts for all units.
     *
     * @param int $userId
     * @return array A nested assoc array: [unit_key => [category_key => item_key]]
     */
    public function getUnitLoadouts(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT unit_key, category_key, item_key FROM user_unit_loadouts WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
        
        $loadouts = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $loadouts[$row['unit_key']][$row['category_key']] = $row['item_key'];
        }
        
        // Returns [ 'soldier' => [ 'main_weapon' => 'pulse_rifle' ] ]
        return $loadouts;
    }

    /**
     * Atomically updates the quantity of a single item in a user's inventory.
     * This handles all cases: adding new items, adding to a stack, or consuming a stack.
     *
     * @param int $userId
     * @param string $itemKey
     * @param int $quantityChange A positive (e.g., 20) or negative (e.g., -20) number.
     * @return bool
     */
    public function updateItemQuantity(int $userId, string $itemKey, int $quantityChange): bool
    {
        $sql = "
            INSERT INTO user_armory_inventory (user_id, item_key, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
                quantity = GREATEST(0, CAST(quantity AS SIGNED) + CAST(? AS SIGNED))
        ";
        
        // We cast to SIGNED to allow for negative numbers, and GREATEST(0, ...)
        // prevents the quantity from ever dropping below 0.
        return $this->db->prepare($sql)->execute([
            $userId, 
            $itemKey, 
            max(0, $quantityChange), // Initial insert must be non-negative
            $quantityChange          // The update value
        ]);
    }

    /**
     * Atomically sets or updates the equipped item for a specific unit/category slot.
     *
     * @param int $userId
     * @param string $unitKey (e.g., 'soldier')
     * @param string $categoryKey (e.g., 'main_weapon')
     * @param string $itemKey (e.g., 'pulse_rifle')
     * @return bool
     */
    public function setLoadout(int $userId, string $unitKey, string $categoryKey, string $itemKey): bool
    {
        $sql = "
            INSERT INTO user_unit_loadouts (user_id, unit_key, category_key, item_key)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                item_key = VALUES(item_key)
        ";
        
        return $this->db->prepare($sql)->execute([$userId, $unitKey, $categoryKey, $itemKey]);
    }

    /**
     * --- NEW METHOD TO FIX THE BUG ---
     * Clears a loadout slot by deleting the row.
     *
     * @param int $userId
     * @param string $unitKey
     * @param string $categoryKey
     * @return bool
     */
    public function clearLoadoutSlot(int $userId, string $unitKey, string $categoryKey): bool
    {
        $sql = "
            DELETE FROM user_unit_loadouts 
            WHERE user_id = ? AND unit_key = ? AND category_key = ?
        ";
        
        return $this->db->prepare($sql)->execute([$userId, $unitKey, $categoryKey]);
    }
}