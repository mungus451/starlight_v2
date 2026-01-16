<?php

namespace App\Models\Entities;

/**
 * Represents a single row from the 'alliance_roles' table.
 */
readonly class AllianceRole
{
    /**
     * @param int $id
     * @param int $alliance_id
     * @param string $name
     * @param int $sort_order
     * @param int $permissions A bitmask of permissions.
     */
    public function __construct(
        public int $id,
        public int $alliance_id,
        public string $name,
        public int $sort_order,
        public int $permissions
    ) {
    }

    /**
     * @param int $permission
     * @return bool
     */
    public function hasPermission(int $permission): bool
    {
        return ($this->permissions & $permission) === $permission;
    }
}