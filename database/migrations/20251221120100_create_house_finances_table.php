<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHouseFinancesTable extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('house_finances')) {
            // If table exists, ensure at least one seed row exists (id=1)
            $row = $this->fetchRow('SELECT COUNT(*) AS c FROM `house_finances`');
            if ($row && (int)$row['c'] === 0) {
                $this->execute("INSERT INTO `house_finances` (`id`, `credits_taxed`, `crystals_taxed`) VALUES (1, 0.0000, 0.0000)");
            }
            return;
        }

        $this->table('house_finances', [
            'id' => 'id',
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('credits_taxed', 'decimal', ['precision' => 19, 'scale' => 4, 'null' => false, 'default' => '0.0000', 'comment' => 'Total credits collected as conversion fees'])
            ->addColumn('crystals_taxed', 'decimal', ['precision' => 19, 'scale' => 4, 'null' => false, 'default' => '0.0000', 'comment' => 'Total crystals collected as conversion fees'])
            ->create();

        // Seed initial row to match current schema state
        $this->execute("INSERT INTO `house_finances` (`id`, `credits_taxed`, `crystals_taxed`) VALUES (1, 0.0000, 0.0000)");
    }
}
