<?php

namespace App\Models\Entities;

readonly class WeaponDefinition
{
    public function __construct(
        public int $id,
        public string $name,
        public string $type,
        public int $attack_bonus,
        public int $defense_bonus,
        public int $cost_credits,
        public int $cost_gemstones,
        public int $cost_research_data,
        public string $created_at,
        public string $updated_at
    ) {
    }
}
