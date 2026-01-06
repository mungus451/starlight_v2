<?php

use Phinx\Migration\AbstractMigration;

class AddDarkMatterTaxedToHouseFinances extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('house_finances');
        if (!$table->hasColumn('dark_matter_taxed')) {
            $table->addColumn('dark_matter_taxed', 'decimal', [
                'precision' => 40,
                'scale' => 18,
                'default' => 0.000000000000000000,
                'after' => 'crystals_taxed'
            ])->update();
        }
    }
}
