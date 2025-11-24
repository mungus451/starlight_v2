<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUserArmoryInventory extends AbstractMigration
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
        // Create `user_armory_inventory` table if it does not already exist
        if (!$this->hasTable('user_armory_inventory')) {
            $table = $this->table('user_armory_inventory', [
                'id' => false,
                'primary_key' => ['user_id', 'item_key'],
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);

            $table
                ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('item_key', 'string', ['limit' => 50, 'null' => false, 'comment' => "e.g., 'pulse_rifle'"])
                ->addColumn('quantity', 'integer', ['signed' => false, 'default' => 0, 'null' => false])
                ->addForeignKey('user_id', 'users', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_armory_inv_user'
                ])
                ->create();
        }
    }
}
