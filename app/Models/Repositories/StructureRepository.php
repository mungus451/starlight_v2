<?php

namespace App\Models\Repositories;

use App\Models\Entities\UserStructure;
use PDO;

/**
 * Handles all database operations for the 'user_structures' table.
 */
class StructureRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Finds a user's structures by their user ID.
     *
     * @param int $userId
     * @return UserStructure|null
     */
    public function findByUserId(int $userId): ?UserStructure
    {
        $stmt = $this->db->prepare("SELECT * FROM user_structures WHERE user_id = ?");
        $stmt->execute([$userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Creates the default structures row for a new user.
     *
     * @param int $userId
     */
    public function createDefaults(int $userId): void
    {
        $stmt = $this->db->prepare("INSERT INTO user_structures (user_id) VALUES (?)");
        $stmt->execute([$userId]);
    }

    /**
     * --- NEW: Method for Structures Page ---
     * Updates the level of a single structure for a user.
     *
     * @param int $userId
     * @param string $columnName The database column to update (e.g., 'fortification_level')
     * @param int $newLevel The new level for the structure
     * @return bool True on success
     */
    public function updateStructureLevel(int $userId, string $columnName, int $newLevel): bool
    {
        // --- SECURITY: Whitelist column names to prevent SQL injection ---
        $allowedColumns = [
            'economy_upgrade_level',
            'population_level',
            'armory_level',
            'planetary_shield_level',
            'mercenary_outpost_level',
            'neural_uplink_level',
            'subspace_scanner_level'
        ];

        if (!in_array($columnName, $allowedColumns)) {
            // If the column name isn't allowed, log an error and fail
            error_log("Attempted to update non-whitelisted column: " . $columnName);
            return false;
        }

        // The column name is safe to inject into the query
        $sql = "UPDATE user_structures SET `{$columnName}` = ? WHERE user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$newLevel, $userId]);
    }


    /**
     * Sums the levels of ALL structures for all members of an alliance.
     *
     * @param int $allianceId
     * @return int
     */
    public function getAggregateStructureLevelForAlliance(int $allianceId): int
    {
        // Sum all columns that end in '_level'
        $columns = [
            'economy_upgrade_level', 'population_level',
            'armory_level', 'planetary_shield_level',
            'mercenary_outpost_level',
            'neural_uplink_level', 'subspace_scanner_level'
        ];

        $sumExpr = implode(' + ', array_map(fn($c) => "s.$c", $columns));

        $sql = "
            SELECT SUM($sumExpr) as total
            FROM user_structures s
            JOIN users u ON s.user_id = u.id
            WHERE u.alliance_id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$allianceId]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Helper method to convert a database row (array) into a UserStructure entity.
     *
     * @param array $data
     * @return UserStructure
     */
    private function hydrate(array $data): UserStructure
    {
        return new UserStructure(
            user_id: (int)$data['user_id'],
            economy_upgrade_level: (int)$data['economy_upgrade_level'],
            population_level: (int)$data['population_level'],
            armory_level: (int)$data['armory_level'],
            planetary_shield_level: (int)($data['planetary_shield_level'] ?? 0),
            mercenary_outpost_level: (int)($data['mercenary_outpost_level'] ?? 0),
            neural_uplink_level: (int)($data['neural_uplink_level'] ?? 0),
            subspace_scanner_level: (int)($data['subspace_scanner_level'] ?? 0),
        );
    }
            }
            