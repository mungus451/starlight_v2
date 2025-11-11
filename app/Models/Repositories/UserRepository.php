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
     *
     * @param string $email
     * @return User|null Returns a User object or null if not found.
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
     *
     * @param string $charName
     * @return User|null Returns a User object or null if not found.
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
     *
     * @param int $id
     * @return User|null
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
     *
     * @param string $email
     * @param string $charName
     * @param string $passwordHash
     * @return int The ID of the newly created user.
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
     *
     * @param int $userId
     * @param string $bio
     * @param string $pfpUrl
     * @param string $phone
     * @return bool True on success
     */
    public function updateProfile(int $userId, string $bio, string $pfpUrl, string $phone): bool
    {
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
     *
     * @param int $userId
     * @param string $newEmail
     * @return bool True on success
     */
    public function updateEmail(int $userId, string $newEmail): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET email = ? WHERE id = ?");
        return $stmt->execute([$newEmail, $userId]);
    }

    /**
     * Updates a user's password hash.
     *
     * @param int $userId
     * @param string $newPasswordHash
     * @return bool True on success
     */
    public function updatePassword(int $userId, string $newPasswordHash): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        return $stmt->execute([$newPasswordHash, $userId]);
    }

    /**
     * Gets a simple array of all user IDs in the game.
     *
     * @return int[]
     */
    public function getAllUserIds(): array
    {
        $stmt = $this->db->query("SELECT id FROM users ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    // --- METHODS FOR ALLIANCE SERVICE ---

    /**
     * Assigns a user to an alliance with a specific role.
     *
     * @param int $userId
     * @param int $allianceId
     * @param string $role
     * @return bool True on success
     */
    public function setAlliance(int $userId, int $allianceId, string $role): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET alliance_id = ?, alliance_role = ? WHERE id = ?"
        );
        return $stmt->execute([$allianceId, $role, $userId]);
    }

    /**
     * Removes a user from their alliance.
     *
     * @param int $userId
     * @return bool True on success
     */
    public function leaveAlliance(int $userId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET alliance_id = NULL, alliance_role = NULL WHERE id = ?"
        );
        return $stmt->execute([$userId]);
    }

    /**
     * Finds all users who are members of a specific alliance.
     *
     * @param int $allianceId
     * @return User[]
     */
    public function findAllByAllianceId(int $allianceId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE alliance_id = ? ORDER BY character_name ASC");
        $stmt->execute([$allianceId]);
        
        $users = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = $this->hydrate($row);
        }
        return $users;
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
            alliance_role: $data['alliance_role'] ?? null,
            passwordHash: $data['password_hash'],
            createdAt: $data['created_at']
        );
    }
}