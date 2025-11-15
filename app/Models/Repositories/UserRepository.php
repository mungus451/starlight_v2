<?php

namespace App\Models\Repositories;

use App\Core\Database;
use App\Models\Entities\User;
use PDO;

/**
 * Handles all database operations for the 'users' table.
 */
class UserRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Finds a user by their email address.
     */
    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Finds a user by their character name.
     */
    public function findByCharacterName(string $charName): ?User
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE character_name = ?");
        $stmt->execute([$charName]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Finds a user by their ID.
     */
    public function findById(int $id): ?User
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Creates a new user in the database.
     */
    public function createUser(string $email, string $charName, string $passwordHash): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO users (email, character_name, password_hash) VALUES (?, ?, ?)"
        );
        $stmt->execute([$email, $charName, $passwordHash]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Updates the non-sensitive profile information for a user.
     * ---
     * FIX: Changed $bio, $pfpUrl, and $phone to be nullable (?string)
     * to match the service logic and prevent TypeErrors.
     * ---
     */
    public function updateProfile(int $userId, ?string $bio, ?string $pfpUrl, ?string $phone): bool
    {
        // SQL already handles null values correctly, so no changes needed here.
        // We just needed to update the method signature above.
        $bio = empty($bio) ? null : $bio;
        $pfpUrl = empty($pfpUrl) ? null : $pfpUrl;
        $phone = empty($phone) ? null : $phone;

        $stmt = $this->db->prepare(
            "UPDATE users SET bio = ?, profile_picture_url = ?, phone_number = ? WHERE id = ?"
        );
        return $stmt->execute([$bio, $pfpUrl, $phone, $userId]);
    }

    /**
     * Updates a user's email address.
     */
    public function updateEmail(int $userId, string $newEmail): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET email = ? WHERE id = ?");
        return $stmt->execute([$newEmail, $userId]);
    }

    /**
     * Updates a user's password hash.
     */
    public function updatePassword(int $userId, string $newPasswordHash): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        return $stmt->execute([$newPasswordHash, $userId]);
    }

    /**
     * Gets a simple array of all user IDs in the game.
     */
    public function getAllUserIds(): array
    {
        $stmt = $this->db->query("SELECT id FROM users ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    // --- REFACTORED/NEW METHODS FOR ALLIANCE SERVICE ---

    /**
     * Assigns a user to an alliance with a specific role ID.
     *
     * @param int $userId
     * @param int $allianceId
     * @param int $roleId
     * @return bool True on success
     */
    public function setAlliance(int $userId, int $allianceId, int $roleId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET alliance_id = ?, alliance_role_id = ? WHERE id = ?"
        );
        return $stmt->execute([$allianceId, $roleId, $userId]);
    }
    
    /**
     * Updates a user's role ID within their alliance.
     *
     * @param int $userId
     * @param int $newRoleId
     * @return bool
     */
    public function setAllianceRole(int $userId, int $newRoleId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET alliance_role_id = ? WHERE id = ?"
        );
        return $stmt->execute([$newRoleId, $userId]);
    }

    /**
     * Removes a user from their alliance (sets fields to NULL).
     *
     * @param int $userId
     * @return bool True on success
     */
    public function leaveAlliance(int $userId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET alliance_id = NULL, alliance_role_id = NULL WHERE id = ?"
        );
        return $stmt->execute([$userId]);
    }

    /**
     * Finds all users who are members of a specific alliance.
     * This now JOINS alliance_roles to get the role name.
     *
     * @param int $allianceId
     * @return array (Note: Returns an array of associative arrays, not User Entities)
     */
    public function findAllByAllianceId(int $allianceId): array
    {
        $sql = "
            SELECT u.*, ar.name as alliance_role_name 
            FROM users u
            LEFT JOIN alliance_roles ar ON u.alliance_role_id = ar.id
            WHERE u.alliance_id = ? 
            ORDER BY ar.sort_order ASC, u.character_name ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$allianceId]);
        
        // We are returning a custom array, not hydrating a full User object,
        // because we have the extra 'alliance_role_name' field.
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- END ALLIANCE METHODS ---

    /**
     * Helper method to convert a database row (array) into a User entity.
     *
     * @param array $data
     * @return User
     */
    private function hydrate(array $data): User
    {
        return new User(
            id: (int)$data['id'],
            email: $data['email'],
            characterName: $data['character_name'],
            bio: $data['bio'] ?? null,
            profile_picture_url: $data['profile_picture_url'] ?? null,
            phone_number: $data['phone_number'] ?? null,
            alliance_id: isset($data['alliance_id']) ? (int)$data['alliance_id'] : null,
            alliance_role_id: isset($data['alliance_role_id']) ? (int)$data['alliance_role_id'] : null, // This line was changed
            passwordHash: $data['password_hash'],
            createdAt: $data['created_at']
        );
    }
}