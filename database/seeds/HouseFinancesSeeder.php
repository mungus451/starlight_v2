<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class HouseFinancesSeeder extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Seeds the house_finances table with initial row (id=1)
     */
    public function run(): void
    {
        $adapter = $this->getAdapter();

        // Check if seed row already exists
        $stmt = $adapter->query('SELECT COUNT(*) AS c FROM `house_finances` WHERE `id` = 1');
        $row = $stmt->fetch();

        if ($row && (int)$row['c'] > 0) {
            // Seed row already exists, skip
            return;
        }

        // Insert initial row using Phinx abstraction
        $this->table('house_finances')->insert([
            'id' => 1,
            'credits_taxed' => '0.0000',
            'crystals_taxed' => '0.0000',
        ])->saveData();
    }
}
