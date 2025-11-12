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

    // --- NEW METHOD FOR PHASE 13 ---
    
    /**
     * Updates an alliance's public profile.
     *
     * @param int $allianceId
     * @param string $description
     * @param string $pfpUrl
     * @return bool
     */
    public function updateProfile(int $allianceId, string $description, string $pfpUrl): bool
    {
        $description = empty($description) ? null : $description;
        $pfpUrl = empty($pfpUrl) ? null : $pfpUrl;

        $stmt = $this->db->prepare(
            "UPDATE alliances SET description = ?, profile_picture_url = ? WHERE id = ?"
        );
        return $stmt->execute([$description, $pfpUrl, $allianceId]);
    }

    // --- END NEW METHOD ---

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
            leader_id: (int)$data['leader_id'],
            net_worth: (int)$data['net_worth'],
            created_at: $data['created_at']
        );
    }
}