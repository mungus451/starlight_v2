<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RefactorAllianceRolePermissions extends AbstractMigration
{
    public function up(): void
    {
        // 1. Add the new permissions column
        $table = $this->table('alliance_roles');
        $table->addColumn('permissions', 'biginteger', ['signed' => false, 'default' => 0, 'after' => 'sort_order'])
              ->update();

        // 2. Define the permission mapping
        $permissionMap = [
            'can_edit_profile' => 1 << 0,  // 1
            'can_manage_applications' => 1 << 1,  // 2
            'can_invite_members' => 1 << 2,  // 4
            'can_kick_members' => 1 << 3,  // 8
            'can_manage_roles' => 1 << 4,  // 16
            'can_see_private_board' => 1 << 5,  // 32
            'can_manage_forum' => 1 << 6,  // 64
            'can_manage_bank' => 1 << 7,  // 128
            'can_manage_structures' => 1 << 8,  // 256
            'can_manage_diplomacy' => 1 << 9,  // 512
            'can_declare_war' => 1 << 10, // 1024
        ];

        // 3. Migrate the data
        $roles = $this->fetchAll('SELECT * FROM alliance_roles');
        foreach ($roles as $role) {
            $newPermissions = 0;
            foreach ($permissionMap as $column => $bit) {
                if ($role[$column]) {
                    $newPermissions |= $bit;
                }
            }

            $this->execute(
                sprintf(
                    'UPDATE alliance_roles SET permissions = %d WHERE id = %d',
                    $newPermissions,
                    $role['id']
                )
            );
        }

        // 4. Drop the old permission columns
        $table = $this->table('alliance_roles');
        foreach (array_keys($permissionMap) as $columnName) {
            $table->removeColumn($columnName);
        }
        $table->update();
    }

    public function down(): void
    {
        // 1. Define the permission mapping (same as above)
        $permissionMap = [
            'can_edit_profile' => 1 << 0,
            'can_manage_applications' => 1 << 1,
            'can_invite_members' => 1 << 2,
            'can_kick_members' => 1 << 3,
            'can_manage_roles' => 1 << 4,
            'can_see_private_board' => 1 << 5,
            'can_manage_forum' => 1 << 6,
            'can_manage_bank' => 1 << 7,
            'can_manage_structures' => 1 << 8,
            'can_manage_diplomacy' => 1 << 9,
            'can_declare_war' => 1 << 10,
        ];

        // 2. Add the old columns back
        $table = $this->table('alliance_roles');
        foreach (array_keys($permissionMap) as $columnName) {
            $table->addColumn($columnName, 'boolean', ['default' => 0, 'null' => false]);
        }
        $table->update();

        // 3. Restore the data
        $roles = $this->fetchAll('SELECT id, permissions FROM alliance_roles');
        foreach ($roles as $role) {
            foreach ($permissionMap as $column => $bit) {
                $hasPermission = ($role['permissions'] & $bit) === $bit;
                $this->execute(
                    sprintf(
                        'UPDATE alliance_roles SET %s = %d WHERE id = %d',
                        $column,
                        $hasPermission ? 1 : 0,
                        $role['id']
                    )
                );
            }
        }

        // 4. Drop the new permissions column
        $table->removeColumn('permissions')->update();
    }
}
