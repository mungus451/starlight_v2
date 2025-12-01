<?php

namespace App\Models\Entities;

/**
 * Represents a single transaction record in the Black Market / Undermarket.
 * Immutable DTO.
 */
readonly class BlackMarketLog
{
    /**
     * @param int $id
     * @param int $user_id
     * @param string $action_type ('conversion', 'purchase', 'bounty', 'shadow_contract')
     * @param string|null $item_key (e.g., 'void_container', 'stat_respec')
     * @param string $cost_currency ('credits', 'crystals')
     * @param float $cost_amount
     * @param string|null $details_json Raw JSON string of metadata
     * @param string $created_at
     */
    public function __construct(
        public int $id,
        public int $user_id,
        public string $action_type,
        public ?string $item_key,
        public string $cost_currency,
        public float $cost_amount,
        public ?string $details_json,
        public string $created_at
    ) {
    }

    /**
     * Decode the metadata JSON into an array.
     * 
     * @return array
     */
    public function getDetails(): array
    {
        if (empty($this->details_json)) {
            return [];
        }
        return json_decode($this->details_json, true) ?? [];
    }
}