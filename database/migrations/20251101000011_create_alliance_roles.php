<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAllianceRoles extends AbstractMigration
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
        // Create `alliance_roles` table if it does not already exist
        if (!$this->hasTable('alliance_roles')) {
            $table = $this->table('alliance_roles', [
                'id' => 'id',
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);

            $table
                ->addColumn('alliance_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('name', 'string', ['limit' => 100, 'null' => false])
                ->addColumn('sort_order', 'integer', ['default' => 10, 'null' => false])
                // Permission Flags
                ->addColumn('can_edit_profile', 'boolean', ['default' => 0, 'null' => false])
                ->addColumn('can_manage_applications', 'boolean', ['default' => 0, 'null' => false])
                ->addColumn('can_invite_members', 'boolean', ['default' => 0, 'null' => false])
                ->addColumn('can_kick_members', 'boolean', ['default' => 0, 'null' => false])
                ->addColumn('can_manage_roles', 'boolean', ['default' => 0, 'null' => false])
                ->addColumn('can_see_private_board', 'boolean', ['default' => 0, 'null' => false])
                ->addColumn('can_manage_forum', 'boolean', ['default' => 0, 'null' => false])
                ->addColumn('can_manage_bank', 'boolean', ['default' => 0, 'null' => false])
                ->addColumn('can_manage_structures', 'boolean', ['default' => 0, 'null' => false])
                ->addColumn('can_manage_diplomacy', 'boolean', ['default' => 0, 'null' => false])
                ->addColumn('can_declare_war', 'boolean', ['default' => 0, 'null' => false])
                ->addForeignKey('alliance_id', 'alliances', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_role_alliance'
                ])
                ->create();
        }
    }
}
