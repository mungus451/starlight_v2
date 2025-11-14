<?php

/**
 * Bank Configuration
 *
 * This file stores all "magic numbers" for the banking system,
 * including deposit limits and charge regeneration.
 */

return [
    /**
     * The maximum number of deposit charges a user can accumulate.
     */
    'deposit_max_charges' => 4,

    /**
     * The number of hours it takes to regenerate a single deposit charge.
     */
    'deposit_charge_regen_hours' => 6,

    /**
     * The maximum percentage (as a decimal) of on-hand credits a user
     * can deposit in a single transaction. 0.80 = 80%
     */
    'deposit_percent_limit' => 0.80,
];