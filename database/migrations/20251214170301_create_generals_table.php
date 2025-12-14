<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateGeneralsTable extends AbstractMigration
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
    public function up(): void
    {
        if (!$this->hasTable('generals')) {
            $table = $this->table('generals');
            $table->addColumn('user_id', 'integer', ['signed' => false])
                  ->addColumn('name', 'string', ['limit' => 255])
                  ->addColumn('experience', 'integer', ['default' => 0])
                  ->addColumn('weapon_slot_1', 'integer', ['null' => true])
                  ->addColumn('weapon_slot_2', 'integer', ['null' => true])
                  ->addColumn('weapon_slot_3', 'integer', ['null' => true])
                  ->addColumn('weapon_slot_4', 'integer', ['null' => true])
                  ->addColumn('armor_slot_1', 'integer', ['null' => true])
                  ->addColumn('armor_slot_2', 'integer', ['null' => true])
                  ->addColumn('armor_slot_3', 'integer', ['null' => true])
                  ->addColumn('armor_slot_4', 'integer', ['null' => true])
                  ->addTimestamps()
                  ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                  ->addIndex(['user_id'], ['unique' => true])
                  ->create();
        }
    }

    public function down(): void
    {
        $this->table('generals')->drop()->save();
    }
}
