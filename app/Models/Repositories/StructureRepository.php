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
            'fortification_level',
            'offense_upgrade_level',
            'defense_upgrade_level',
            'spy_upgrade_level',
            'economy_upgrade_level',
            'population_level',
            'armory_level',
            'accounting_firm_level',
            'quantum_research_lab_level',
            'nanite_forge_level',
            'dark_matter_siphon_level',
            'planetary_shield_level',
            'naquadah_mining_complex_level',
            'protoform_vat_level',
            'weapon_vault_level',
            'embassy_level'
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
     * Helper method to convert a database row (array) into a UserStructure entity.
     *
     * @param array $data
     * @return UserStructure
     */
    private function hydrate(array $data): UserStructure
    {
        return new UserStructure(
            user_id: (int)$data['user_id'],
            fortification_level: (int)$data['fortification_level'],
            offense_upgrade_level: (int)$data['offense_upgrade_level'],
            defense_upgrade_level: (int)$data['defense_upgrade_level'],
            spy_upgrade_level: (int)$data['spy_upgrade_level'],
            economy_upgrade_level: (int)$data['economy_upgrade_level'],
            population_level: (int)$data['population_level'],
            armory_level: (int)$data['armory_level'],
            accounting_firm_level: (int)$data['accounting_firm_level'],
            quantum_research_lab_level: (int)($data['quantum_research_lab_level'] ?? 0),
            nanite_forge_level: (int)($data['nanite_forge_level'] ?? 0),
                        dark_matter_siphon_level: (int)($data['dark_matter_siphon_level'] ?? 0),
                        planetary_shield_level: (int)($data['planetary_shield_level'] ?? 0),
                        naquadah_mining_complex_level: (int)($data['naquadah_mining_complex_level'] ?? 0),
                        protoform_vat_level: (int)($data['protoform_vat_level'] ?? 0),
                        weapon_vault_level: (int)($data['weapon_vault_level'] ?? 0),
                        embassy_level: (int)($data['embassy_level'] ?? 0)
                    );
                }
            }
            