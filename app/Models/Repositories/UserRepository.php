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
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
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

        if ($data) {
            return $this->hydrate($data);
        }

        return null;
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

        if ($data) {
            return $this->hydrate($data);
        }

        return null;
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
            passwordHash: $data['password_hash'],
            createdAt: $data['created_at']
        );
    }
}