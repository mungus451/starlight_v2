<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUserStats extends AbstractMigration
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
        // Create `user_stats` table if it does not already exist
        if (!$this->hasTable('user_stats')) {
            $table = $this->table('user_stats', [
                'id' => false,
                'primary_key' => ['user_id'],
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);

            $table
                ->addColumn('user_id', 'integer', ['signed' => false])
                ->addColumn('level', 'integer', ['default' => 1, 'null' => false])
                ->addColumn('experience', 'biginteger', ['default' => 0, 'null' => false])
                ->addColumn('net_worth', 'biginteger', ['default' => 500, 'null' => false])
                ->addColumn('war_prestige', 'integer', ['default' => 0, 'null' => false])
                ->addColumn('energy', 'integer', ['default' => 10, 'null' => false])
                ->addColumn('attack_turns', 'integer', ['default' => 10, 'null' => false])
                ->addColumn('level_up_points', 'integer', ['default' => 1, 'null' => false])
                ->addColumn('strength_points', 'integer', ['default' => 0, 'null' => false])
                ->addColumn('constitution_points', 'integer', ['default' => 0, 'null' => false])
                ->addColumn('wealth_points', 'integer', ['default' => 0, 'null' => false])
                ->addColumn('dexterity_points', 'integer', ['default' => 0, 'null' => false])
                ->addColumn('charisma_points', 'integer', ['default' => 0, 'null' => false])
                ->addColumn('deposit_charges', 'integer', ['default' => 4, 'null' => false])
                ->addColumn('last_deposit_at', 'timestamp', ['null' => true, 'default' => null])
                ->addForeignKey('user_id', 'users', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_stats_user'
                ])
                ->create();
        }
    }
}
