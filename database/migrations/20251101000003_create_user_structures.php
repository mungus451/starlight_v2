<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUserStructures extends AbstractMigration
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
        // Create `user_structures` table if it does not already exist
        if (!$this->hasTable('user_structures')) {
            $table = $this->table('user_structures', [
                'id' => false,
                'primary_key' => ['user_id'],
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);

            $table
                ->addColumn('user_id', 'integer', ['signed' => false])
                ->addColumn('fortification_level', 'integer', ['default' => 0, 'null' => false])
                ->addColumn('offense_upgrade_level', 'integer', ['default' => 0, 'null' => false])
                ->addColumn('defense_upgrade_level', 'integer', ['default' => 0, 'null' => false])
                ->addColumn('spy_upgrade_level', 'integer', ['default' => 0, 'null' => false])
                ->addColumn('economy_upgrade_level', 'integer', ['default' => 0, 'null' => false])
                ->addColumn('population_level', 'integer', ['default' => 0, 'null' => false])
                ->addColumn('armory_level', 'integer', ['default' => 0, 'null' => false])
                ->addForeignKey('user_id', 'users', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_structures_user'
                ])
                ->create();
        }
    }
}
