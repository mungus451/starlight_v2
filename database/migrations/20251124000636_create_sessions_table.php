<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSessionsTable extends AbstractMigration
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
        // Create `sessions` table if it does not already exist
        if (!$this->hasTable('sessions')) {
            $table = $this->table('sessions', [
                'id' => false,
                'primary_key' => ['id'],
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);

            $table
                ->addColumn('id', 'string', ['limit' => 128, 'null' => false])
                ->addColumn('user_id', 'integer', ['signed' => false, 'null' => true, 'default' => null])
                ->addColumn('payload', 'text', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => false])
                ->addColumn('last_activity', 'integer', ['signed' => false, 'null' => false])
                ->addIndex(['last_activity'], ['name' => 'idx_last_activity'])
                ->addIndex(['user_id'], ['name' => 'idx_user_id'])
                ->create();
        }
    }
}
