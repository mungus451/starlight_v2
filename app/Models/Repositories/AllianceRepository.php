<?php

namespace App\Models\Repositories;

use App\Models\Entities\Alliance;
use PDO;

/**
 * Handles all database operations for the 'alliances' table.
 */
class AllianceRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Creates a new alliance.
     */
    public function create(string $name, string $tag, int $leaderId): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO alliances (name, tag, leader_id) VALUES (?, ?, ?)"
        );
        $stmt->execute([$name, $tag, $leaderId]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Searches for alliances by name (partial match).
     * Used for Autocomplete.
     * 
     * @return array Array of ['id', 'name', 'tag', 'profile_picture_url']
     */
    public function searchByName(string $query, int $limit = 10): array
    {
        $sql = "
            SELECT id, name, tag, profile_picture_url 
            FROM alliances 
            WHERE name LIKE ? 
            ORDER BY name ASC 
            LIMIT ?
        ";
        $stmt = $this->db->prepare($sql);
        $term = '%' . $query . '%';
        $stmt->bindParam(1, $term, PDO::PARAM_STR);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Finds an alliance by its ID.
     */
    public function findById(int $id): ?Alliance
    {
        $stmt = $this->db->prepare("SELECT * FROM alliances WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Finds an alliance by its name.
     */
    public function findByName(string $name): ?Alliance
    {
        $stmt = $this->db->prepare("SELECT * FROM alliances WHERE name = ?");
        $stmt->execute([$name]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Finds an alliance by its tag.
     */
    public function findByTag(string $tag): ?Alliance
    {
        $stmt = $this->db->prepare("SELECT * FROM alliances WHERE tag = ?");
        $stmt->execute([$tag]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Gets the total number of alliances.
     */
    public function getTotalCount(): int
    {
        $stmt = $this->db->query("SELECT COUNT(id) as total FROM alliances");
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    /**
     * Gets a paginated list of alliances, ranked by net worth.
     * Used for the general alliance list view.
     */
    public function getPaginatedAlliances(int $limit, int $offset): array
    {
        $sql = "
            SELECT * FROM alliances 
            ORDER BY net_worth DESC, id ASC
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->bindParam(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $alliances = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $alliances[] = $this->hydrate($row);
        }
        return $alliances;
    }

    /**
     * --- NEW: Alliance Leaderboard ---
     * Gets a rich array of alliance data including member counts.
     * Returns an array of associative arrays, not Entities, for display.
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getLeaderboardAlliances(int $limit, int $offset): array
    {
        $sql = "
            SELECT 
                a.id, 
                a.name, 
                a.tag, 
                a.net_worth, 
                a.profile_picture_url,
                (SELECT COUNT(*) FROM users u WHERE u.alliance_id = a.id) as member_count
            FROM alliances a
            ORDER BY a.net_worth DESC, id ASC
            LIMIT ? OFFSET ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->bindParam(2, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Updates an alliance's public profile.
     *
     * @param int $allianceId
     * @param string $description
     * @param string $pfpUrl
     * @param bool $isJoinable
     * @return bool
     */
    public function updateProfile(int $allianceId, string $description, string $pfpUrl, bool $isJoinable): bool
    {
        $description = empty($description) ? null : $description;
        $pfpUrl = empty($pfpUrl) ? null : $pfpUrl;

        $stmt = $this->db->prepare(
            "UPDATE alliances SET description = ?, profile_picture_url = ?, is_joinable = ? WHERE id = ?"
        );
        return $stmt->execute([$description, $pfpUrl, (int)$isJoinable, $allianceId]);
    }
    
    /**
     * Atomically updates an alliance's bank balance by a relative amount.
     * (e.g., amountChange = +1000 or -500)
     *
     * @param int $allianceId
     * @param float $amountChange The (positive or negative) amount to change the balance by.
     * @return bool
     */
    public function updateBankCreditsRelative(int $allianceId, float $amountChange): bool
    {
        if ($amountChange === 0.0) {
            return true;
        }

        // Hard Cap: 10^60 (Safe limit for DECIMAL(60,0))
        // We use a string literal to avoid floating point imprecision at this scale
        $safeCap = '1' . str_repeat('0', 60);

        // Convert amount to string to ensure full precision
        $amountStr = number_format($amountChange, 0, '.', '');

        if ($amountChange > 0) {
            // Addition: Cap at safe limit
            $sql = "UPDATE alliances SET bank_credits = LEAST(:cap, bank_credits + :amount) WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'cap' => $safeCap, 
                'amount' => $amountStr, 
                'id' => $allianceId
            ]);
        } else {
            // Subtraction: Prevent dropping below zero
            // FIX: Use distinct parameter names for each placeholder to prevent PDO HY093 errors
            $absChange = abs($amountChange);
            $absStr = number_format($absChange, 0, '.', '');
            
            $sql = "UPDATE alliances SET bank_credits = IF(bank_credits < :absAmount1, 0, bank_credits - :absAmount2) WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'absAmount1' => $absStr, 
                'absAmount2' => $absStr, 
                'id' => $allianceId
            ]);
        }
    }

    /**
     * Gets all alliances from the database.
     * Used by the TurnProcessorService.
     *
     * @return Alliance[]
     */
    public function getAllAlliances(): array
    {
        $stmt = $this->db->query("SELECT * FROM alliances ORDER BY id ASC");
        
        $alliances = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $alliances[] = $this->hydrate($row);
        }
        return $alliances;
    }

    /**
     * Retrieves a lightweight list of ID and Name/Tag for all alliances.
     * Used for Almanac Dropdowns.
     * 
     * @return array Array of ['id', 'name', 'tag']
     */
    public function getAllAlliancesSimple(): array
    {
        $stmt = $this->db->query("SELECT id, name, tag FROM alliances ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Updates an alliance's last_compound_at timestamp.
     * Used by the TurnProcessorService after applying interest.
     *
     * @param int $allianceId
     * @return bool
     */
    public function updateLastCompoundAt(int $allianceId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE alliances SET last_compound_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([$allianceId]);
    }

    /**
     * Updates an alliance's total net worth.
     * Used by the TurnProcessorService after aggregating member stats.
     *
     * @param int $allianceId
     * @param int $netWorth
     * @return bool
     */
    public function updateNetWorth(int $allianceId, int $netWorth): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE alliances SET net_worth = ? WHERE id = ?"
        );
        return $stmt->execute([$netWorth, $allianceId]);
    }

    public function updateDirective(int $allianceId, string $type, int $target, int $startValue): bool
    {
        $sql = "
            UPDATE alliances 
            SET directive_type = ?, 
                directive_target = ?, 
                directive_start_value = ?, 
                directive_started_at = NOW()
            WHERE id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$type, $target, $startValue, $allianceId]);
    }

    /**
     * Helper method to convert a database row into an Alliance entity.
     */
    private function hydrate(array $row): Alliance
    {
        return new Alliance(
            id: (int)$row['id'],
            name: $row['name'],
            tag: $row['tag'],
            description: $row['description'],
            profile_picture_url: $row['profile_picture_url'],
            is_joinable: (bool)$row['is_joinable'],
            leader_id: (int)$row['leader_id'],
            net_worth: (int)$row['net_worth'],
            bank_credits: (float)$row['bank_credits'],
            last_compound_at: $row['last_compound_at'],
            created_at: $row['created_at'],
            directive_type: $row['directive_type'] ?? null,
            directive_target: (int)($row['directive_target'] ?? 0),
            directive_start_value: (int)($row['directive_start_value'] ?? 0),
            directive_started_at: $row['directive_started_at'] ?? null,
            completed_directives: json_decode($row['completed_directives'] ?? '{}', true) ?? []
        );
    }
}