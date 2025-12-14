<?php

namespace App\Models\Entities;

readonly class General
{
    public function __construct(
        public int $id,
        public int $user_id,
        public string $name,
        public int $experience,
        public ?int $weapon_slot_1,
        public ?int $weapon_slot_2,
        public ?int $weapon_slot_3,
        public ?int $weapon_slot_4,
        public ?int $armor_slot_1,
        public ?int $armor_slot_2,
        public ?int $armor_slot_3,
        public ?int $armor_slot_4,
        public string $created_at,
        public string $updated_at
    ) {
    }
}
