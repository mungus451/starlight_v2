<?php

namespace Tests\Unit\Presenters;

use Tests\Unit\TestCase;
use App\Presenters\DashboardPresenter;
use DateTime;

class DashboardPresenterTest extends TestCase
{
    private DashboardPresenter $presenter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->presenter = new DashboardPresenter();
    }

    public function testPresentFormatsSafehouseCooldownEffect(): void
    {
        $future = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');
        $data = [
            'activeEffects' => [
                [
                    'effect_type' => 'safehouse_cooldown',
                    'expires_at' => $future
                ]
            ]
        ];

        $result = $this->presenter->present($data);

        $effect = $result['activeEffects'][0];
        $this->assertEquals('Safehouse Cooldown', $effect['ui_label']);
        $this->assertEquals('fa-user-clock', $effect['ui_icon']);
        $this->assertEquals('text-warning', $effect['ui_color']);
        $this->assertArrayHasKey('formatted_time_left', $effect);
    }

    public function testPresentFormatsBreachPermitEffect(): void
    {
        $future = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');
        $data = [
            'activeEffects' => [
                [
                    'effect_type' => 'safehouse_breach',
                    'expires_at' => $future
                ]
            ]
        ];

        $result = $this->presenter->present($data);

        $effect = $result['activeEffects'][0];
        $this->assertEquals('Breach Permit', $effect['ui_label']);
        $this->assertEquals('fa-key', $effect['ui_icon']);
        $this->assertEquals('text-info', $effect['ui_color']);
    }

    public function testPresentFormatsHighRiskProtocolEffect(): void
    {
        $future = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');
        $data = [
            'activeEffects' => [
                [
                    'effect_type' => 'high_risk_protocol',
                    'expires_at' => $future
                ]
            ]
        ];

        $result = $this->presenter->present($data);

        $effect = $result['activeEffects'][0];
        $this->assertEquals('High Risk Protocol', $effect['ui_label']);
        $this->assertEquals('fa-biohazard', $effect['ui_icon']);
        $this->assertEquals('text-danger', $effect['ui_color']);
    }

    public function testPresentFormatsUnknownEffect(): void
    {
        $future = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');
        $data = [
            'activeEffects' => [
                [
                    'effect_type' => 'non_existent_effect',
                    'expires_at' => $future
                ]
            ]
        ];

        $result = $this->presenter->present($data);

        $effect = $result['activeEffects'][0];
        $this->assertEquals('Unknown Effect', $effect['ui_label']);
        $this->assertEquals('fa-bolt', $effect['ui_icon']);
    }
    
    public function testPresentFiltersExpiredEffects(): void
    {
        $past = (new DateTime('-1 hour'))->format('Y-m-d H:i:s');
        $data = [
            'activeEffects' => [
                [
                    'effect_type' => 'jamming',
                    'expires_at' => $past
                ]
            ]
        ];

        $result = $this->presenter->present($data);

        $this->assertEmpty($result['activeEffects']);
    }

    public function testPresentFormatsNumbers(): void
    {
        $data = [
            'naquadah_crystals' => 1500,
            'naquadah_per_turn' => 1234567
        ];

        $result = $this->presenter->present($data);

        $this->assertEquals('1.5K', $result['formatted_naquadah_crystals']);
        $this->assertEquals('1.2M', $result['formatted_naquadah_per_turn']);
    }
}
