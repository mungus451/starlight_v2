<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddWarObjectiveToAllianceStructures extends AbstractMigration
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
        $table = $this->table('alliance_structures');
        $table->addColumn('is_war_objective', 'boolean', [
            'default' => false,
            'null' => false,
            'after' => 'level' // Place it after the 'level' column for logical grouping
        ])
        ->addIndex(['is_war_objective'])
        ->update();
    }
}
