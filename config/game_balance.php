<?php

/**
 * Game Balance Configuration
 *
 * This file stores all the "magic numbers" for game balance,
 * such as unit costs, build times, etc.
 */

return [
    'training' => [
        // Unit costs are [ 'credits' => X, 'citizens' => Y ]
        'workers'  => ['credits' => 100, 'citizens' => 1],
        'soldiers' => ['credits' => 1000, 'citizens' => 1],
        'guards'   => ['credits' => 2500, 'citizens' => 1],
        'spies'    => ['credits' => 10000, 'citizens' => 1],
        'sentries' => ['credits' => 5000, 'citizens' => 1],
    ],

    // We can add other balance sections here later, e.g.:
    // 'structures' => [ ... ]
    // 'attack' => [ ... ]
];