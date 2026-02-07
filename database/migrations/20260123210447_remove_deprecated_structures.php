<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RemoveDeprecatedStructures extends AbstractMigration
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
        $this->table('user_structures')
            ->removeColumn('fortification_level')
            ->removeColumn('offense_upgrade_level')
            ->removeColumn('defense_upgrade_level')
            ->removeColumn('spy_upgrade_level')
            ->removeColumn('accounting_firm_level')
            ->removeColumn('quantum_research_lab_level')
            ->removeColumn('nanite_forge_level')
            ->removeColumn('dark_matter_siphon_level')
            ->removeColumn('naquadah_mining_complex_level')
            ->removeColumn('protoform_vat_level')
            ->removeColumn('weapon_vault_level')
            ->removeColumn('embassy_level')
            ->removeColumn('fusion_plant_level')
            ->removeColumn('orbital_trade_port_level')
            ->removeColumn('banking_datacenter_level')
            ->removeColumn('cloning_vats_level')
            ->removeColumn('war_college_level')
            ->removeColumn('phase_bunker_level')
            ->removeColumn('ion_cannon_network_level')
            ->save();

        $this->table('spy_reports')
            ->removeColumn('fortification_level_seen')
            ->removeColumn('offense_upgrade_level_seen')
            ->removeColumn('defense_upgrade_level_seen')
            ->removeColumn('spy_upgrade_level_seen')
            ->save();
    }

    public function down(): void
    {
        $this->table('user_structures')
            ->addColumn('fortification_level', 'integer', ['default' => 0])
            ->addColumn('offense_upgrade_level', 'integer', ['default' => 0])
            ->addColumn('defense_upgrade_level', 'integer', ['default' => 0])
            ->addColumn('spy_upgrade_level', 'integer', ['default' => 0])
            ->addColumn('accounting_firm_level', 'integer', ['default' => 0])
            ->addColumn('quantum_research_lab_level', 'integer', ['default' => 0])
            ->addColumn('nanite_forge_level', 'integer', ['default' => 0])
            ->addColumn('dark_matter_siphon_level', 'integer', ['default' => 0])
            ->addColumn('naquadah_mining_complex_level', 'integer', ['default' => 0])
            ->addColumn('protoform_vat_level', 'integer', ['default' => 0])
            ->addColumn('weapon_vault_level', 'integer', ['default' => 0])
            ->addColumn('embassy_level', 'integer', ['default' => 0])
            ->addColumn('fusion_plant_level', 'integer', ['default' => 0])
            ->addColumn('orbital_trade_port_level', 'integer', ['default' => 0])
            ->addColumn('banking_datacenter_level', 'integer', ['default' => 0])
            ->addColumn('cloning_vats_level', 'integer', ['default' => 0])
            ->addColumn('war_college_level', 'integer', ['default' => 0])
            ->addColumn('phase_bunker_level', 'integer', ['default' => 0])
            ->addColumn('ion_cannon_network_level', 'integer', ['default' => 0])
            ->save();

        $this->table('spy_reports')
            ->addColumn('fortification_level_seen', 'integer', ['null' => true])
            ->addColumn('offense_upgrade_level_seen', 'integer', ['null' => true])
            ->addColumn('defense_upgrade_level_seen', 'integer', ['null' => true])
            ->addColumn('spy_upgrade_level_seen', 'integer', ['null' => true])
            ->save();
    }
}
