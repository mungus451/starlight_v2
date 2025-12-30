<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHouseFinancesTable extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('house_finances')) {
            return;
        }

        $this->table('house_finances', [
                'id' => 'id',
                'signed' => false,
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ])
            ->addColumn('credits_taxed', 'decimal', ['precision' => 19, 'scale' => 4, 'null' => false, 'default' => '0.0000', 'comment' => 'Total credits collected as conversion fees'])
            ->addColumn('crystals_taxed', 'decimal', ['precision' => 19, 'scale' => 4, 'null' => false, 'default' => '0.0000', 'comment' => 'Total crystals collected as conversion fees'])
            ->create();
    }
}
