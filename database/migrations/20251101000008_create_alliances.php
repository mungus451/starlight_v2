<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAlliances extends AbstractMigration
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
        // Create `alliances` table if it does not already exist
        if (!$this->hasTable('alliances')) {
            $table = $this->table('alliances', [
                'id' => 'id',
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);

            $table
                ->addColumn('name', 'string', ['limit' => 100, 'null' => false])
                ->addColumn('tag', 'string', ['limit' => 5, 'null' => false])
                ->addColumn('description', 'text', ['null' => true, 'default' => null])
                ->addColumn('profile_picture_url', 'string', ['limit' => 255, 'null' => true, 'default' => null])
                ->addColumn('is_joinable', 'boolean', ['default' => 0, 'null' => false, 'comment' => '0=Application, 1=Open'])
                ->addColumn('leader_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('net_worth', 'biginteger', ['default' => 0, 'null' => false])
                ->addColumn('bank_credits', 'biginteger', ['default' => 0, 'null' => false, 'signed' => false])
                ->addColumn('last_compound_at', 'timestamp', ['null' => true, 'default' => null])
                ->addTimestamps(updatedAt: false)
                ->addIndex(['name'], ['unique' => true, 'name' => 'uk_name'])
                ->addIndex(['tag'], ['unique' => true, 'name' => 'uk_tag'])
                ->addForeignKey('leader_id', 'users', 'id', [
                    'delete' => 'RESTRICT',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_alliance_leader'
                ])
                ->create();
        }
    }
}
