<?php

namespace App\Models\Services;

use App\Models\Entities\User;
use App\Models\Entities\AllianceRole;

/**
 * Handles all "yes/no" authorization logic for alliance actions.
 * This service centralizes permission checks.
 */
class AlliancePolicyService
{
    /**
     * Checks if an admin can perform an action on a target user.
     * This is a generic "can-they-manage-them" check.
     *
     * @param User $adminUser
     * @param AllianceRole $adminRole
     * @param User $targetUser
     * @param AllianceRole|null $targetRole
     * @param string $permissionName (e.g., 'can_kick_members')
     * @return string|null Null on success, or a string error message on failure.
     */
    private function canManageTarget(
        User $adminUser,
        AllianceRole $adminRole,
        User $targetUser,
        ?AllianceRole $targetRole,
        string $permissionName
    ): ?string {
        // 1. Check if admin and target are in the same alliance
        if ($adminUser->alliance_id === null || $adminUser->alliance_id !== $targetUser->alliance_id) {
            return 'This user is not in your alliance.';
        }

        // 2. Check if admin has the base permission
        if (!property_exists($adminRole, $permissionName) || $adminRole->{$permissionName} !== true) {
            return 'You do not have permission to ' . str_replace('_', ' ', $permissionName) . '.';
        }
        
        // 3. Check if target is the Leader
        if ($targetRole && $targetRole->name === 'Leader') {
            return 'You cannot manage the alliance Leader.';
        }
        
        // 4. Check for hierarchy (admin must have a lower sort_order, i.e., higher rank)
        // If target has no role, they can be managed.
        if ($targetRole && $adminRole->sort_order >= $targetRole->sort_order) {
            return 'You can only manage members with a lower role than your own.';
        }

        // All checks passed
        return null;
    }

    /**
     * Checks if an admin can kick a target user.
     *
     * @param User $adminUser
     * @param AllianceRole $adminRole
     * @param User $targetUser
     * @param AllianceRole|null $targetRole
     * @return string|null Null on success, or a string error message on failure.
     */
    public function canKick(
        User $adminUser,
        AllianceRole $adminRole,
        User $targetUser,
        ?AllianceRole $targetRole
    ): ?string {
        return $this->canManageTarget($adminUser, $adminRole, $targetUser, $targetRole, 'can_kick_members');
    }

    /**
     * Checks if an admin can assign a new role to a target user.
     *
     * @param User $adminUser
     * @param AllianceRole $adminRole
     * @param User $targetUser
     * @param AllianceRole|null $targetRole
     * @param AllianceRole $newRole
     * @return string|null Null on success, or a string error message on failure.
     */
    public function canAssignRole(
        User $adminUser,
        AllianceRole $adminRole,
        User $targetUser,
        ?AllianceRole $targetRole,
        AllianceRole $newRole
    ): ?string {
        // 1. Run the base management checks (checks for permission, hierarchy, etc.)
        $manageError = $this->canManageTarget($adminUser, $adminRole, $targetUser, $targetRole, 'can_manage_roles');
        if ($manageError !== null) {
            return $manageError;
        }

        // 2. Check if the new role is valid
        if ($newRole->alliance_id !== $adminUser->alliance_id) {
            return 'The new role is not part of your alliance.';
        }

        // 3. Check if the new role is 'Leader'
        if ($newRole->name === 'Leader') {
            return 'You cannot promote a member to the Leader role.';
        }

        // 4. Check if the new role is of a lower rank than the admin
        if ($adminRole->sort_order >= $newRole->sort_order) {
            return 'You can only assign roles with a lower rank than your own.';
        }

        // All checks passed
        return null;
    }
}