<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUsersAllianceColumns extends AbstractMigration
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
        // Add alliance columns to users table if they don't exist
        $table = $this->table('users');

        if (!$table->hasColumn('alliance_id')) {
            $table
                ->addColumn('alliance_id', 'integer', [
                    'signed' => false,
                    'null' => true,
                    'default' => null,
                    'after' => 'phone_number'
                ])
                ->addColumn('alliance_role', 'string', [
                    'limit' => 50,
                    'null' => true,
                    'default' => null,
                    'after' => 'alliance_id'
                ])
                ->addForeignKey('alliance_id', 'alliances', 'id', [
                    'delete' => 'SET_NULL',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_user_alliance'
                ])
                ->update();
        }
    }
}
