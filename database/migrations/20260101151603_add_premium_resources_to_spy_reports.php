<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPremiumResourcesToSpyReports extends AbstractMigration
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
        $table = $this->table('spy_reports');
        $table->addColumn('naquadah_crystals_stolen', 'decimal', ['precision' => 19, 'scale' => 4, 'default' => 0, 'after' => 'credits_seen'])
              ->addColumn('dark_matter_stolen', 'integer', ['default' => 0, 'after' => 'naquadah_crystals_stolen'])
              ->addColumn('naquadah_crystals_seen', 'decimal', ['precision' => 19, 'scale' => 4, 'null' => true, 'default' => null, 'after' => 'dark_matter_stolen'])
              ->addColumn('dark_matter_seen', 'integer', ['null' => true, 'default' => null, 'after' => 'naquadah_crystals_seen'])
              ->update();
    }
}