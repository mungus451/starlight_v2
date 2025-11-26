<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUsersIsNpcColumn extends AbstractMigration
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
        // Add is_npc column to users table if it doesn't exist
        $table = $this->table('users');

        if (!$table->hasColumn('is_npc')) {
            $table
                ->addColumn('is_npc', 'boolean', [
                    'default' => 0,
                    'null' => false,
                    'after' => 'alliance_role_id'
                ])
                ->addIndex(['is_npc'], ['name' => 'idx_is_npc'])
                ->update();
        }
    }
}
