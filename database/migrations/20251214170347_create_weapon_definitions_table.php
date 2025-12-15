<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateWeaponDefinitionsTable extends AbstractMigration
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
        $table = $this->table('weapon_definitions', ['signed' => false]);
        $table->addColumn('name', 'string', ['limit' => 255])
              ->addColumn('type', 'enum', ['values' => ['weapon', 'armor']])
              ->addColumn('attack_bonus', 'integer', ['default' => 0])
              ->addColumn('defense_bonus', 'integer', ['default' => 0])
              ->addColumn('cost_credits', 'integer', ['default' => 0])
              ->addColumn('cost_gemstones', 'integer', ['default' => 0])
              ->addColumn('cost_research_data', 'integer', ['default' => 0])
              ->addTimestamps()
              ->addIndex(['type'])
              ->create();
    }

    public function down(): void
    {
        $this->table('weapon_definitions')->drop()->save();
    }
}
