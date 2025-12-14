<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddNewStructuresToUserStructures extends AbstractMigration
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
        $table = $this->table('user_structures');
        $table->addColumn('protoform_vat_level', 'integer', ['default' => 0, 'null' => false])
              ->addColumn('weapon_vault_level', 'integer', ['default' => 0, 'null' => false])
              ->addColumn('galactic_market_level', 'integer', ['default' => 0, 'null' => false])
              ->addColumn('embassy_level', 'integer', ['default' => 0, 'null' => false])
              ->update();
    }
}
