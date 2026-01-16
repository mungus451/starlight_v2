<?php

namespace App\Models\Entities;

readonly class Operation
{
    public function __construct(
        public int $id,
        public int $alliance_id,
        public string $type,
        public int $target_value,
        public int $current_value,
        public string $deadline,
        public string $status,
        public ?string $reward_buff,
        public string $created_at
    ) {}

    public function getProgressPercent(): int
    {
        if ($this->target_value <= 0) return 100;
        $pct = ($this->current_value / $this->target_value) * 100;
        return (int)min(100, max(0, $pct));
    }

    public function isExpired(): bool
    {
        return strtotime($this->deadline) < time();
    }
}
