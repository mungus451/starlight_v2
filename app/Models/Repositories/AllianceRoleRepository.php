<?php

namespace App\Models\Repositories;

use App\Models\Entities\AllianceRole;
use PDO;

/**
 * Handles all database operations for the 'alliance_roles' table.
 */
class AllianceRoleRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Creates a new alliance role.
     *
     * @param int $allianceId
     * @param string $name
     * @param int $order
     * @param int $permissions A bitmask of permissions
     * @return int The ID of the new role
     */
    public function create(int $allianceId, string $name, int $order, int $permissions): int
    {
        $sql = "
            INSERT INTO alliance_roles 
                (alliance_id, name, sort_order, permissions) 
            VALUES 
                (?, ?, ?, ?)
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $allianceId,
            $name,
            $order,
            $permissions
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Finds a role by its ID.
     *
     * @param int|null $roleId
     * @return AllianceRole|null
     */
    public function findById(?int $roleId): ?AllianceRole
    {
        if ($roleId === null) {
            return null;
        }
        
        $stmt = $this->db->prepare("SELECT * FROM alliance_roles WHERE id = ?");
        $stmt->execute([$roleId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Finds all roles for a specific alliance, ordered by their sort_order.
     *
     * @param int $allianceId
     * @return AllianceRole[]
     */
    public function findByAllianceId(int $allianceId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM alliance_roles WHERE alliance_id = ? ORDER BY sort_order ASC, name ASC");
        $stmt->execute([$allianceId]);
        
        $roles = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $roles[] = $this->hydrate($row);
        }
        return $roles;
    }

    /**
     * Finds a default role for an alliance by its name (e.g., "Recruit").
     *
     * @param int $allianceId
     * @param string $name
     * @return AllianceRole|null
     */
    public function findDefaultRole(int $allianceId, string $name): ?AllianceRole
    {
        $stmt = $this->db->prepare("SELECT * FROM alliance_roles WHERE alliance_id = ? AND name = ?");
        $stmt->execute([$allianceId, $name]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Updates a role's name and permissions.
     *
     * @param int $roleId
     * @param string $name
     * @param int $permissions
     * @return bool
     */
    public function update(int $roleId, string $name, int $permissions): bool
    {
        $sql = "
            UPDATE alliance_roles SET
                name = ?,
                permissions = ?
            WHERE id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $name,
            $permissions,
            $roleId
        ]);
    }

    /**
     * Deletes a role.
     *
     * @param int $roleId
     * @return bool
     */
    public function delete(int $roleId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM alliance_roles WHERE id = ?");
        return $stmt->execute([$roleId]);
    }

    /**
     * Reassigns all members of an old role to a new role.
     * (Used before deleting a role).
     *
     * @param int $oldRoleId
     * @param int $newRoleId
     * @return bool
     */
    public function reassignRoleMembers(int $oldRoleId, int $newRoleId): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET alliance_role_id = ? WHERE alliance_role_id = ?");
        return $stmt->execute([$newRoleId, $oldRoleId]);
    }

    /**
     * Helper method to convert a database row into an AllianceRole entity.
     */
    private function hydrate(array $data): AllianceRole
    {
        return new AllianceRole(
            id: (int)$data['id'],
            alliance_id: (int)$data['alliance_id'],
            name: $data['name'],
            sort_order: (int)$data['sort_order'],
            permissions: (int)$data['permissions']
        );
    }
}