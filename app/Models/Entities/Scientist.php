<?php

namespace App\Models\Entities;

readonly class Scientist
{
    public function __construct(
        public int $id,
        public int $user_id,
        public string $name,
        public bool $is_active,
        public string $created_at,
        public string $updated_at
    ) {
    }
}
