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
     * @param bool $can_edit_profile
     * @param bool $can_manage_applications
     * @param bool $can_invite_members
     * @param bool $can_kick_members
     * @param bool $can_manage_roles
     * @param bool $can_see_private_board
     * @param bool $can_manage_forum
     * @param bool $can_manage_bank
     * @param bool $can_manage_structures
     * @param bool $can_manage_diplomacy
     * @param bool $can_declare_war (NEW)
     */
    public function __construct(
        public readonly int $id,
        public readonly int $alliance_id,
        public readonly string $name,
        public readonly int $sort_order,
        public readonly bool $can_edit_profile,
        public readonly bool $can_manage_applications,
        public readonly bool $can_invite_members,
        public readonly bool $can_kick_members,
        public readonly bool $can_manage_roles,
        public readonly bool $can_see_private_board,
        public readonly bool $can_manage_forum,
        public readonly bool $can_manage_bank,
        public readonly bool $can_manage_structures,
        public readonly bool $can_manage_diplomacy,
        public readonly bool $can_declare_war // --- NEW ---
    ) {
    }
}