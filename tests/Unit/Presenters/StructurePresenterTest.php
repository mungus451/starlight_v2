<?php

namespace Tests\Unit\Presenters;

use Tests\Unit\TestCase;
use App\Presenters\StructurePresenter;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStructure;
use Mockery;

class StructurePresenterTest extends TestCase
{
    private StructurePresenter $presenter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->presenter = new StructurePresenter();
    }

    public function testPresentMethodFormatsAllStructuresCorrectly(): void
    {
        // Arrange
        $userId = 1;
        $resources = new UserResource($userId, credits: 10000000, banked_credits: 0, gemstones: 0, naquadah_crystals: 500.0, untrained_citizens: 0, workers: 0, soldiers: 0, guards: 0, spies: 0, sentries: 0, untraceable_chips: 0, research_data: 0, dark_matter: 500.0);
        $structures = new UserStructure(
            user_id: $userId, 
            fortification_level: 1, 
            offense_upgrade_level: 1, 
            defense_upgrade_level: 1, 
            spy_upgrade_level: 1, 
            economy_upgrade_level: 1, 
            population_level: 1, 
            armory_level: 1, 
            accounting_firm_level: 2, 
            quantum_research_lab_level: 1, 
            nanite_forge_level: 1, 
            dark_matter_siphon_level: 2, 
            planetary_shield_level: 1,
            naquadah_mining_complex_level: 2
        );

        // Mock the data returned from StructureService::getStructureData
        $structureFormulas = [
            'fortification' => [
                'name' => 'Fortification',
                'base_cost' => 100000,
                'multiplier' => 1.8,
                'category' => 'Defense',
                'description' => 'Increases the base power of your Guards and overall structural integrity.'
            ],
            'offense_upgrade' => [
                'name' => 'Offense Upgrade',
                'base_cost' => 50000,
                'multiplier' => 2.0,
                'category' => 'Offense',
                'description' => 'Increases the base power of your Soldiers in offensive operations.'
            ],
            'economy_upgrade' => [
                'name' => 'Economy Upgrade',
                'base_cost' => 200000,
                'multiplier' => 1.7,
                'category' => 'Economy',
                'description' => 'Increases your passive credit income generated each turn.'
            ],
            'accounting_firm' => [
                'name' => 'Accounting Firm',
                'base_cost' => 10000,
                'base_crystal_cost' => 250,
                'multiplier' => 1.9,
                'category' => 'Economy',
                'description' => 'Increases passive credit income by 1% per level.'
            ],
            'dark_matter_siphon' => [
                'name' => 'Dark Matter Siphon',
                'base_cost' => 2500000,
                'base_crystal_cost' => 1000,
                'multiplier' => 2.2,
                'category' => 'Advanced Industry',
                'description' => 'Generates rare Dark Matter used for constructing superstructures and advanced weaponry.'
            ],
            'naquadah_mining_complex' => [
                'name' => 'Naquadah Mining Complex',
                'base_cost' => 1000000,
                'base_dark_matter_cost' => 1,
                'multiplier' => 2.0,
                'category' => 'Advanced Industry',
                'description' => 'Generates Naquadah Crystals each turn.'
            ],
        ];

        $serviceData = [
            'resources' => $resources,
            'structures' => $structures,
            'costs' => [
                'fortification' => ['credits' => 100000, 'crystals' => 0, 'dark_matter' => 0],
                'economy_upgrade' => ['credits' => 200000, 'crystals' => 0, 'dark_matter' => 0],
                'naquadah_mining_complex' => ['credits' => 1000000, 'crystals' => 0, 'dark_matter' => 500],
                'dark_matter_siphon' => ['credits' => 2500000, 'crystals' => 1000, 'dark_matter' => 0],
                'accounting_firm' => ['credits' => 10000, 'crystals' => 0, 'dark_matter' => 0],
            ],
            'structureFormulas' => $structureFormulas,
            'turnConfig' => [
                'credit_income_per_econ_level' => 1000,
                'citizen_growth_per_pop_level' => 1,
                'naquadah_per_mining_complex_level' => 10,
                'naquadah_production_multiplier' => 1.01,
                'dark_matter_per_siphon_level' => 0.5,
                'dark_matter_production_multiplier' => 1.02,
                'accounting_firm_base_bonus' => 0.01,
                'accounting_firm_multiplier' => 1.05,
            ],
            'attackConfig' => [
                'power_per_offense_level' => 0.05,
                'power_per_fortification_level' => 0.1,
                'power_per_defense_level' => 0.1,
            ],
            'spyConfig' => [
                'offense_power_per_level' => 0.1,
            ],
        ];

        // Act
        $grouped = $this->presenter->present($serviceData);

        // Assert basic structure
        $this->assertArrayHasKey('Economy', $grouped);
        $this->assertArrayHasKey('Advanced Industry', $grouped);
        $this->assertArrayHasKey('Defense', $grouped);

        // Assert Naquadah Mining Complex (Dark Matter Cost + Compounding Benefit)
        $naquadahMine = null;
        foreach ($grouped['Advanced Industry'] as $struct) {
            if ($struct['key'] === 'naquadah_mining_complex') {
                $naquadahMine = $struct;
                break;
            }
        }
        $this->assertNotNull($naquadahMine);
        $this->assertEquals(2, $naquadahMine['current_level']);
        $this->assertEquals('1,000,000 C + 500 🌌', $naquadahMine['cost_formatted']);
        $this->assertTrue($naquadahMine['can_afford']);

        // Naquadah output: Base 10 * Level 2 * (1.01 ^ (2-1)) = 20 * 1.01 = 20.2
        // Next Level: Base 10 * Level 3 * (1.01 ^ 2) = 30 * 1.0201 = 30.603
        $this->assertEquals('Output: 20.20 / Turn (Next: 30.60)', $naquadahMine['benefit_text']);
        
        // Accounting Firm
        $accountingFirm = null;
        foreach ($grouped['Economy'] as $struct) {
            if ($struct['key'] === 'accounting_firm') {
                $accountingFirm = $struct;
                break;
            }
        }
        $this->assertNotNull($accountingFirm);
        $this->assertEquals(2, $accountingFirm['current_level']);
        $this->assertEquals('10,000 C', $accountingFirm['cost_formatted']);
        // Accountign Firm Output: Base 0.01 * Level 2 * (1.05 ^ (2-1)) = 0.02 * 1.05 = 0.021
        // Next: Base 0.01 * Level 3 * (1.05 ^ 2) = 0.03 * 1.1025 = 0.033075
        $this->assertEquals('Bonus: 2.10% (Next: 3.31%)', $accountingFirm['benefit_text']);
        
        // Dark Matter Siphon
        $darkMatterSiphon = null;
        foreach ($grouped['Advanced Industry'] as $struct) {
            if ($struct['key'] === 'dark_matter_siphon') {
                $darkMatterSiphon = $struct;
                break;
            }
        }
        $this->assertNotNull($darkMatterSiphon);
        $this->assertEquals(2, $darkMatterSiphon['current_level']);
        $this->assertEquals('2,500,000 C + 1,000 💎', $darkMatterSiphon['cost_formatted']);
        // Dark Matter Output: Base 0.5 * Level 2 * (1.02 ^ (2-1)) = 1 * 1.02 = 1.02
        // Next: Base 0.5 * Level 3 * (1.02 ^ 2) = 1.5 * 1.0404 = 1.5606
        $this->assertEquals('Output: 1.02 / Turn (Next: 1.56)', $darkMatterSiphon['benefit_text']);
    }

    public function testPresentMethodHandlesMaxLevelAndInsufficientResources(): void
    {
        // Arrange
        $userId = 1;
        // User has enough credits but not dark matter
        $resources = new UserResource($userId, credits: 10000000, banked_credits: 0, gemstones: 0, naquadah_crystals: 0.0, untrained_citizens: 0, workers: 0, soldiers: 0, guards: 0, spies: 0, sentries: 0, untraceable_chips: 0, research_data: 0, dark_matter: 10.0); 
        
        // Naquadah is max level (no cost for next) and another structure is level 1
        $structures = new UserStructure(
            user_id: $userId,
            fortification_level: 0,
            offense_upgrade_level: 0,
            defense_upgrade_level: 0,
            spy_upgrade_level: 0,
            economy_upgrade_level: 0,
            population_level: 0,
            armory_level: 0,
            accounting_firm_level: 0,
            quantum_research_lab_level: 0,
            nanite_forge_level: 0,
            dark_matter_siphon_level: 0,
            planetary_shield_level: 0,
            naquadah_mining_complex_level: 99 // Effectively max level if cost formula makes next cost 0
        );

        $serviceData = [
            'resources' => $resources,
            'structures' => $structures,
            'costs' => [
                'naquadah_mining_complex' => ['credits' => 0, 'crystals' => 0, 'dark_matter' => 0], // Max level
                'dark_matter_siphon' => ['credits' => 2500000, 'crystals' => 0, 'dark_matter' => 500], // Needs 500 DM, has 10
            ],
            'structureFormulas' => [
                'naquadah_mining_complex' => [
                    'name' => 'Naquadah Mining Complex',
                    'base_cost' => 1000000,
                    'base_dark_matter_cost' => 1,
                    'multiplier' => 2.0,
                    'category' => 'Advanced Industry',
                    'description' => 'Generates Naquadah Crystals each turn.'
                ],
                'dark_matter_siphon' => [
                    'name' => 'Dark Matter Siphon',
                    'base_cost' => 2500000,
                    'base_crystal_cost' => 1000,
                    'multiplier' => 2.2,
                    'category' => 'Advanced Industry',
                    'description' => 'Generates rare Dark Matter used for constructing superstructures and advanced weaponry.'
                ],
            ],
            'turnConfig' => [
                'naquadah_per_mining_complex_level' => 10,
                'naquadah_production_multiplier' => 1.01,
                'dark_matter_per_siphon_level' => 0.5,
                'dark_matter_production_multiplier' => 1.02,
            ],
            'attackConfig' => [],
            'spyConfig' => [],
        ];

        // Act
        $grouped = $this->presenter->present($serviceData);

        // Assert Naquadah is max level
        $naquadahMine = $grouped['Advanced Industry'][0];
        $this->assertNotNull($naquadahMine);
        $this->assertTrue($naquadahMine['is_max_level']);
        // The status_class is derived from can_afford, not is_max_level directly. So this assertion was incorrect.
        // $this->assertEquals('Max Level Achieved!', $naquadahMine['status_class']); 
        
        // Assert Dark Matter Siphon is unaffordable
        $darkMatterSiphon = null;
        foreach ($grouped['Advanced Industry'] as $struct) {
            if ($struct['key'] === 'dark_matter_siphon') {
                $darkMatterSiphon = $struct;
                break;
            }
        }
        $this->assertNotNull($darkMatterSiphon);
        $this->assertFalse($darkMatterSiphon['can_afford']);
        $this->assertEquals('insufficient', $darkMatterSiphon['status_class']);

        // Assert Naquadah is max level (all costs are 0)
        $naquadahMine = null;
        foreach ($grouped['Advanced Industry'] as $struct) {
            if ($struct['key'] === 'naquadah_mining_complex') {
                $naquadahMine = $struct;
                break;
            }
        }
        $this->assertNotNull($naquadahMine);
        $this->assertTrue($naquadahMine['is_max_level']);
    }
}
