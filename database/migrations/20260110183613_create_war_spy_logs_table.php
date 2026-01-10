<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateWarSpyLogsTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('war_spy_logs');
        $table->addColumn('war_id', 'integer', ['null' => false])
              ->addColumn('spy_report_id', 'integer', ['null' => true, 'comment' => 'Link to original spy report if applicable'])
              ->addColumn('attacker_user_id', 'integer', ['null' => false, 'comment' => 'User who performed the spy op'])
              ->addColumn('attacker_alliance_id', 'integer', ['null' => false])
              ->addColumn('defender_user_id', 'integer', ['null' => false, 'comment' => 'User targeted by the spy op'])
              ->addColumn('defender_alliance_id', 'integer', ['null' => false])
              ->addColumn('operation_type', 'string', ['limit' => 50, 'null' => false, 'comment' => 'e.g., intel, sabotage, counter'])
              ->addColumn('result', 'string', ['limit' => 50, 'null' => false, 'comment' => 'e.g., success, failure, caught'])
              ->addTimestamps()
              ->addIndex(['war_id'])
              ->addIndex(['attacker_user_id'])
              ->addIndex(['defender_user_id'])
              ->create();
    }
}