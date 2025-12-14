<?php

namespace App\Models\Entities;

readonly class DoctrineDefinition
{
    public function __construct(
        public int $id,
        public string $name,
        public string $description,
        public string $type,
        public string $effect,
        public float $value,
        public string $created_at,
        public string $updated_at
    ) {
    }
}
