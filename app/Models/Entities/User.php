<?php

namespace App\Models\Entities;

/**
 * Represents a single user row from the database.
 * This is a "dumb" data object.
 */
class User
{
    /**
     * @param int $id The user's unique ID
     * @param string $email The user's unique email
     * @param string $characterName The user's unique character name
     * @param string $passwordHash The user's hashed password
     * @param string $createdAt The timestamp of when the user was created
     */
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly string $characterName,
        public readonly string $passwordHash,
        public readonly string $createdAt
    ) {
    }
}