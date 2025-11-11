<?php

namespace App\Models\Repositories;

use App\Models\Entities\AllianceApplication;
use PDO;

/**
 * Handles all database operations for the 'alliance_applications' table.
 */
class ApplicationRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Creates a new application.
     *
     * @param int $userId
     * @param int $allianceId
     * @return bool
     */
    public function create(int $userId, int $allianceId): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO alliance_applications (user_id, alliance_id) VALUES (?, ?)"
        );
        return $stmt->execute([$userId, $allianceId]);
    }

    /**
     * Finds a pending application by its ID.
     *
     * @param int $appId
     * @return AllianceApplication|null
     */
    public function findById(int $appId): ?AllianceApplication
    {
        $stmt = $this->db->prepare("SELECT * FROM alliance_applications WHERE id = ?");
        $stmt->execute([$appId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Checks if a user already has a pending application to a specific alliance.
     *
     * @param int $userId
     * @param int $allianceId
     * @return AllianceApplication|null
     */
    public function findByUserAndAlliance(int $userId, int $allianceId): ?AllianceApplication
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM alliance_applications WHERE user_id = ? AND alliance_id = ?"
        );
        $stmt->execute([$userId, $allianceId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Finds all pending applications for a specific alliance.
     * Joins the users table to get the applicant's name.
     *
     * @param int $allianceId
     * @return AllianceApplication[]
     */
    public function findByAllianceId(int $allianceId): array
    {
        $sql = "
            SELECT aa.*, u.character_name 
            FROM alliance_applications aa
            JOIN users u ON aa.user_id = u.id
            WHERE aa.alliance_id = ?
            ORDER BY aa.created_at ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$allianceId]);
        
        $apps = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $apps[] = $this->hydrate($row);
        }
        return $apps;
    }

    /**
     * Deletes a specific application by its ID.
     *
     * @param int $appId
     * @return bool
     */
    public function delete(int $appId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM alliance_applications WHERE id = ?");
        return $stmt->execute([$appId]);
    }

    /**
     * Deletes all applications for a specific user.
     * (Called when a user joins ANY alliance)
     *
     * @param int $userId
     * @return bool
     */
    public function deleteByUser(int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM alliance_applications WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }


    /**
     * Helper method to convert a database row into an AllianceApplication entity.
     *
     * @param array $data
     * @return AllianceApplication
     */
    private function hydrate(array $data): AllianceApplication
    {
        return new AllianceApplication(
            id: (int)$data['id'],
            user_id: (int)$data['user_id'],
            alliance_id: (int)$data['alliance_id'],
            created_at: $data['created_at'],
            character_name: $data['character_name'] ?? null
        );
    }
}