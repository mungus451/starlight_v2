<?php

namespace App\Models\Entities;

/**
 * Represents an active edict for a user.
 */
readonly class UserEdict
{
    public function __construct(
        public int $id,
        public int $user_id,
        public string $edict_key,
        public string $created_at
    ) {}
}
