<?php

namespace App\Models\Entities;

readonly class UserDoctrine
{
    public function __construct(
        public int $id,
        public int $user_id,
        public int $doctrine_id,
        public string $created_at,
        public string $updated_at
    ) {
    }
}
