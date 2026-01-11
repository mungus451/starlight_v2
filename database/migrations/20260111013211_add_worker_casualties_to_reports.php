<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddWorkerCasualtiesToReports extends AbstractMigration
{
    public function change(): void
    {
        $battleTable = $this->table('battle_reports');
        if (!$battleTable->hasColumn('defender_workers_lost')) {
            $battleTable->addColumn('defender_workers_lost', 'integer', [
                'default' => 0, 
                'null' => false, 
                'signed' => false,
                'after' => 'defender_guards_lost' // Corrected column name
            ])->update();
        }

        $spyTable = $this->table('spy_reports');
        if (!$spyTable->hasColumn('defender_workers_lost')) {
            $spyTable->addColumn('defender_workers_lost', 'integer', [
                'default' => 0, 
                'null' => false, 
                'signed' => false,
                'after' => 'sentries_lost_defender' // Corrected column name
            ])->update();
        }
    }
}