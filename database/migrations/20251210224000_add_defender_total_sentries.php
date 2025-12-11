<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDefenderTotalSentries extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('spy_reports');
        if (!$table->hasColumn('defender_total_sentries')) {
            $table->addColumn('defender_total_sentries', 'integer', [
                'default' => 0,
                'null' => false,
                'after' => 'sentries_lost_defender',
                'comment' => 'Snapshot of defenders total sentry count at start of operation'
            ])
            ->update();
        }
    }
}