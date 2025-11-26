<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateWarHistory extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        // Create `war_history` table if it does not already exist
        if (!$this->hasTable('war_history')) {
            $table = $this->table('war_history', [
                'id' => 'id',
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);

            $table
                ->addColumn('war_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('declarer_name', 'string', ['limit' => 100, 'null' => false])
                ->addColumn('defender_name', 'string', ['limit' => 100, 'null' => false])
                ->addColumn('casus_belli_text', 'text', ['null' => true, 'default' => null])
                ->addColumn('goal_text', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('outcome', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('mvp_metadata_json', 'json', ['null' => true, 'default' => null])
                ->addColumn('final_stats_json', 'json', ['null' => true, 'default' => null])
                ->addColumn('start_time', 'timestamp', ['null' => false])
                ->addColumn('end_time', 'timestamp', ['null' => false])
                ->addIndex(['war_id'], ['unique' => true, 'name' => 'uk_war_id'])
                ->create();
        }
    }
}
