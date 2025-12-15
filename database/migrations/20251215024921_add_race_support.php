<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddRaceSupport extends AbstractMigration
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
        // Create races table
        $races = $this->table('races', [
            'id' => false,
            'primary_key' => ['id'],
            'collation' => 'utf8mb4_unicode_ci',
            'encoding' => 'utf8mb4',
        ]);
        
        $races
            ->addColumn('id', 'integer', ['signed' => false, 'identity' => true])
            ->addColumn('name', 'string', ['limit' => 50])
            ->addColumn('exclusive_resource', 'string', ['limit' => 50])
            ->addColumn('lore', 'text')
            ->addColumn('uses', 'text')
            ->addIndex(['name'], ['unique' => true, 'name' => 'uk_race_name'])
            ->create();
        
        // Insert the 4 races with their lore and uses
        $this->execute("
            INSERT INTO `races` (`id`, `name`, `exclusive_resource`, `lore`, `uses`) VALUES
            (1, 'Aridan Nomads', 'Whisperium Spice', 'Harvested from their desert world, used for psychic navigation and psionic tech.', 'Tier 4+ warp route plotting, advanced medical enhancers'),
            (2, 'Luminarch Order', 'Aurorium Crystals', 'Cultivated from radiant crystal groves attuned to cosmic energy.', 'Advanced energy weapons, hyperdrive cores, phase shields'),
            (3, 'Vorax Brood', 'Xenoplasm Bio-Gel', 'A living bio-organic secretion used in biotech fusion.', 'Self-healing armor, bio-weapon engineering, cybernetic upgrades'),
            (4, 'Synthien Collective', 'Zerulium Cores', 'Exotic quantum matter refined from black hole reactors.', 'Quantum AI cores, teleportation drives, sentient tech construct')
        ");
        
        // Add race_id to users table
        $users = $this->table('users');
        if (!$users->hasColumn('race_id')) {
            $users
                ->addColumn('race_id', 'integer', [
                    'signed' => false,
                    'null' => true,
                    'default' => null,
                    'after' => 'character_name'
                ])
                ->addForeignKey('race_id', 'races', 'id', [
                    'delete' => 'SET_NULL',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_user_race'
                ])
                ->update();
        }
        
        // Add race-exclusive resources to user_resources table
        $resources = $this->table('user_resources');
        
        if (!$resources->hasColumn('whisperium_spice')) {
            $resources->addColumn('whisperium_spice', 'decimal', [
                'precision' => 19,
                'scale' => 4,
                'default' => 0.0000,
                'after' => 'gemstones'
            ])->update();
        }
        
        if (!$resources->hasColumn('aurorium_crystals')) {
            $resources->addColumn('aurorium_crystals', 'decimal', [
                'precision' => 19,
                'scale' => 4,
                'default' => 0.0000,
                'after' => 'whisperium_spice'
            ])->update();
        }
        
        if (!$resources->hasColumn('xenoplasm_biogel')) {
            $resources->addColumn('xenoplasm_biogel', 'decimal', [
                'precision' => 19,
                'scale' => 4,
                'default' => 0.0000,
                'after' => 'aurorium_crystals'
            ])->update();
        }
        
        if (!$resources->hasColumn('zerulium_cores')) {
            $resources->addColumn('zerulium_cores', 'decimal', [
                'precision' => 19,
                'scale' => 4,
                'default' => 0.0000,
                'after' => 'xenoplasm_biogel'
            ])->update();
        }
    }
}
