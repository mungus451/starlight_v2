<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddExpansionStructures extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('user_structures');
        $table->addColumn('fusion_plant_level', 'integer', ['default' => 0])
              ->addColumn('orbital_trade_port_level', 'integer', ['default' => 0])
              ->addColumn('banking_datacenter_level', 'integer', ['default' => 0])
              ->addColumn('cloning_vats_level', 'integer', ['default' => 0])
              ->addColumn('war_college_level', 'integer', ['default' => 0])
              ->addColumn('mercenary_outpost_level', 'integer', ['default' => 0])
              ->addColumn('phase_bunker_level', 'integer', ['default' => 0])
              ->addColumn('ion_cannon_network_level', 'integer', ['default' => 0])
              ->addColumn('neural_uplink_level', 'integer', ['default' => 0])
              ->addColumn('subspace_scanner_level', 'integer', ['default' => 0])
              ->update();
    }
}