<?php

namespace App\Models\Entities;

/**
 * Represents a static Edict Definition from config.
 */
readonly class EdictDefinition
{
    public function __construct(
        public string $key,
        public string $name,
        public string $description,
        public string $lore,
        public string $type, // 'economic', 'military', etc.
        public array $modifiers, // ['credit_income_percent' => 0.15, ...]
        public int $upkeep_cost,
        public string $upkeep_resource
    ) {}

    public static function fromArray(string $key, array $data): self
    {
        return new self(
            key: $key,
            name: $data['name'],
            description: $data['description'],
            lore: $data['lore'] ?? '',
            type: $data['type'],
            modifiers: $data['modifiers'] ?? [],
            upkeep_cost: $data['upkeep_cost'] ?? 0,
            upkeep_resource: $data['upkeep_resource'] ?? 'credits'
        );
    }
}
