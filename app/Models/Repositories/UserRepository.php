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

    // --- NEW METHODS FOR SETTINGS ---

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
        // Convert empty strings to null to be stored correctly in the DB
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

    // --- END NEW METHODS ---

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
            passwordHash: $data['password_hash'],
            createdAt: $data['created_at']
        );
    }
}