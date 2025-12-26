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
     * @param int $amountChange The (positive or negative) amount to change the balance by.
     * @return bool
     */
    public function updateBankCreditsRelative(int $allianceId, int $amountChange): bool
    {
        if ($amountChange === 0) {
            return true;
        }

        // Hard Cap: 9 Quintillion (Safe limit for Signed BIGINT and PHP 64-bit int)
        // 9,000,000,000,000,000,000
        $safeCap = 9000000000000000000;

        if ($amountChange > 0) {
            // Addition: Cap at safe limit
            $sql = "UPDATE alliances SET bank_credits = LEAST(:cap, bank_credits + :amount) WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['cap' => $safeCap, 'amount' => $amountChange, 'id' => $allianceId]);
        } else {
            // Subtraction: Prevent dropping below zero
            $absChange = abs($amountChange);
            $sql = "UPDATE alliances SET bank_credits = IF(bank_credits < :absAmount, 0, bank_credits - :absAmount) WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['absAmount' => $absChange, 'id' => $allianceId]);
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
     * Helper method to convert a database row into an Alliance entity.
     */
    private function hydrate(array $data): Alliance
    {
        return new Alliance(
            id: (int)$data['id'],
            name: $data['name'],
            tag: $data['tag'],
            description: $data['description'] ?? null,
            profile_picture_url: $data['profile_picture_url'] ?? null,
            is_joinable: (bool)$data['is_joinable'],
            leader_id: (int)$data['leader_id'],
            net_worth: (int)$data['net_worth'],
            bank_credits: (int)$data['bank_credits'],
            last_compound_at: $data['last_compound_at'] ?? null,
            created_at: $data['created_at']
        );
    }
}