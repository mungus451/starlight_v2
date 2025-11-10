<?php

namespace App\Models\Entities;

/**
 * Represents a single user row from the database.
 * This is a "dumb" data object.
 */
class User
{
    /**
     * @param int $id
     * @param string $email
     * @param string $characterName
     * @param string|null $bio
     * @param string|null $profile_picture_url
     * @param string|null $phone_number
     * @param string $passwordHash
     * @param string $createdAt
     */
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly string $characterName,
        public readonly ?string $bio,
        public readonly ?string $profile_picture_url,
        public readonly ?string $phone_number,
        public readonly string $passwordHash,
        public readonly string $createdAt
    ) {
    }
}