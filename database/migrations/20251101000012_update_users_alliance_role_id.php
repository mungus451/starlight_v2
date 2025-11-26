<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UpdateUsersAllianceRoleId extends AbstractMigration
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
        $table = $this->table('users');
        
        // Drop old alliance_role column if it exists
        if ($table->hasColumn('alliance_role')) {
            // First, drop the foreign key constraint
            if ($table->hasForeignKey('alliance_id')) {
                $table->dropForeignKey('alliance_id');
            }
            
            // Drop the alliance_role column
            $table->removeColumn('alliance_role')
                  ->update();
        }
        
        // Add new alliance_role_id column if it doesn't exist
        if (!$table->hasColumn('alliance_role_id')) {
            $table
                ->addColumn('alliance_role_id', 'integer', [
                    'signed' => false,
                    'null' => true,
                    'default' => null,
                    'after' => 'alliance_id'
                ])
                // Re-add the alliance foreign key
                ->addForeignKey('alliance_id', 'alliances', 'id', [
                    'delete' => 'SET_NULL',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_user_alliance'
                ])
                // Add the new role foreign key
                ->addForeignKey('alliance_role_id', 'alliance_roles', 'id', [
                    'delete' => 'SET_NULL',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_user_alliance_role'
                ])
                ->update();
        }
    }
}
