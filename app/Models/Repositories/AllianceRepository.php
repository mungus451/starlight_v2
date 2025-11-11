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
     *
     * @param string $name
     * @param string $tag
     * @param int $leaderId
     * @return int The ID of the newly created alliance.
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
     *
     * @param int $id
     * @return Alliance|null
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
     *
     * @param string $name
     * @return Alliance|null
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
     *
     * @param string $tag
     * @return Alliance|null
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
     *
     * @return int
     */
    public function getTotalCount(): int
    {
        $stmt = $this->db->query("SELECT COUNT(id) as total FROM alliances");
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    /**
     * Gets a paginated list of alliances, ranked by net worth.
     *
     * @param int $limit
     * @param int $offset
     * @return array
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
     * Helper method to convert a database row into an Alliance entity.
     *
     * @param array $data
     * @return Alliance
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