<?php

use Phinx\Migration\AbstractMigration;

class AddNaquadahStructuresAndResources extends AbstractMigration
{
    public function change()
    {
        // Add Naquadah Crystals to User Resources
        $resources = $this->table('user_resources');
        if (!$resources->hasColumn('naquadah_crystals')) {
            $resources->addColumn('naquadah_crystals', 'decimal', [
                'precision' => 19,
                'scale' => 4,
                'default' => 0.0000,
                'after' => 'gemstones'
            ])->update();
        }

        // Add Naquadah Mining Complex Level to User Structures
        $structures = $this->table('user_structures');
        if (!$structures->hasColumn('naquadah_mining_complex_level')) {
            $structures->addColumn('naquadah_mining_complex_level', 'integer', [
                'default' => 0,
                'after' => 'planetary_shield_level'
            ])->update();
        }
    }
}
