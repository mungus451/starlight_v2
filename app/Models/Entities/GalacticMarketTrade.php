<?php

namespace App\Models\Entities;

readonly class GalacticMarketTrade
{
    public function __construct(
        public int $id,
        public int $user_id,
        public string $resource_type,
        public string $trade_type,
        public int $amount,
        public int $price,
        public string $created_at,
        public string $updated_at
    ) {
    }
}
