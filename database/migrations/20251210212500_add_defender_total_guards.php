<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDefenderTotalGuards extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('battle_reports');
        if (!$table->hasColumn('defender_total_guards')) {
            $table->addColumn('defender_total_guards', 'integer', [
                'default' => 0,
                'null' => false,
                'after' => 'defender_defense_power', // Group with other snapshot stats
                'comment' => 'Snapshot of defenders total army size at start of battle'
            ])
            ->update();
        }
    }
}