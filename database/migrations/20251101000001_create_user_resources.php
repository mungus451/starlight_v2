<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUserResources extends AbstractMigration
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
        // Create `user_resources` table if it does not already exist
        if (!$this->hasTable('user_resources')) {
            $table = $this->table('user_resources', [
                'id' => false,
                'primary_key' => ['user_id'],
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);

            $table
                ->addColumn('user_id', 'integer', ['signed' => false])
                ->addColumn('credits', 'biginteger', ['default' => 10000000, 'null' => false, 'signed' => false])
                ->addColumn('banked_credits', 'biginteger', ['default' => 0, 'null' => false, 'signed' => false])
                ->addColumn('gemstones', 'biginteger', ['default' => 0, 'null' => false, 'signed' => false])
                ->addColumn('untrained_citizens', 'integer', ['default' => 250, 'null' => false])
                ->addColumn('workers', 'integer', ['default' => 0, 'null' => false])
                ->addColumn('soldiers', 'integer', ['default' => 0, 'null' => false])
                ->addColumn('guards', 'integer', ['default' => 0, 'null' => false])
                ->addColumn('spies', 'integer', ['default' => 0, 'null' => false])
                ->addColumn('sentries', 'integer', ['default' => 0, 'null' => false])
                ->addForeignKey('user_id', 'users', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_resources_user'
                ])
                ->create();
        }
    }
}
