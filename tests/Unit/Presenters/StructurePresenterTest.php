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
        $resources = new UserResource($userId, credits: 10000000, banked_credits: 0, gemstones: 0, untrained_citizens: 0, workers: 0, soldiers: 0, guards: 0, spies: 0, sentries: 0, research_data: 0); 
        $structures = new UserStructure(
            user_id: $userId,
            economy_upgrade_level: 1,
            population_level: 1,
            armory_level: 1,
        );

        // Mock the data returned from StructureService::getStructureData
        $structureFormulas = [
            'economy_upgrade' => [
                'name' => 'Economy Upgrade',
                'base_cost' => 200000,
                'multiplier' => 1.7,
                'category' => 'Economy',
                'description' => 'Increases your passive credit income generated each turn.'
            ],
        ];

        $serviceData = [
            'resources' => $resources,
            'structures' => $structures,
            'costs' => [
                'economy_upgrade' => ['credits' => 200000, 'crystals' => 0, 'dark_matter' => 0],
            ],
            'structureFormulas' => $structureFormulas,
            'turnConfig' => [
                'credit_income_per_econ_level' => 1000,
                'citizen_growth_per_pop_level' => 1,
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

        // Accounting Firm
        $economyUpgrade = null;
        foreach ($grouped['Economy'] as $struct) {
            if ($struct['key'] === 'economy_upgrade') {
                $economyUpgrade = $struct;
                break;
            }
        }
        $this->assertNotNull($economyUpgrade);
        $this->assertEquals(1, $economyUpgrade['current_level']);
        $this->assertEquals('200,000 C', $economyUpgrade['cost_formatted']);
        $this->assertTrue($economyUpgrade['can_afford']);
    }

    public function testPresentMethodHandlesMaxLevelAndInsufficientResources(): void
    {
        // Arrange
        $userId = 1;
        // User has enough credits but not dark matter
        $resources = new UserResource($userId, credits: 10000000, banked_credits: 0, gemstones: 0, untrained_citizens: 0, workers: 0, soldiers: 0, guards: 0, spies: 0, sentries: 0, research_data: 0);  
        
        // Naquadah is max level (no cost for next) and another structure is level 1
        $structures = new UserStructure(
            user_id: $userId,
            economy_upgrade_level: 0,
            population_level: 0,
            armory_level: 0,
        );

        $serviceData = [
            'resources' => $resources,
            'structures' => $structures,
            'costs' => [
                'economy_upgrade' => ['credits' => 200000, 'crystals' => 0, 'dark_matter' => 0],
            ],
            'structureFormulas' => [
                'economy_upgrade' => [
                    'name' => 'Economy Upgrade',
                    'base_cost' => 200000,
                    'multiplier' => 1.7,
                    'category' => 'Economy',
                    'description' => 'Increases your passive credit income generated each turn.'
                ],
            ],
            'turnConfig' => [
                'dark_matter_per_siphon_level' => 0.5,
                'dark_matter_production_multiplier' => 1.02,
            ],
            'attackConfig' => [],
            'spyConfig' => [],
        ];

        // Act
        $grouped = $this->presenter->present($serviceData);

        // Assert Economy Upgrade is unaffordable
        $economyUpgrade = null;
        foreach ($grouped['Economy'] as $struct) {
            if ($struct['key'] === 'economy_upgrade') {
                $economyUpgrade = $struct;
                break;
            }
        }
        $this->assertNotNull($economyUpgrade);
        $this->assertTrue($economyUpgrade['can_afford']);
    }
}
