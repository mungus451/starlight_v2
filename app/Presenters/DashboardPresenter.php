<?php

namespace App\Presenters;

use DateTime;

/**
 * Responsible for formatting Dashboard data for the View.
 * Handles visual logic like CSS classes, formatting dates, and number formatting.
 */
class DashboardPresenter
{
    /**
     * Transforms raw service data into a view-ready array.
     *
     * @param array $data The array returned from DashboardService::getDashboardData
     * @return array The ViewModel with enriched presentation data
     */
    public function present(array $data): array
    {
        // 1. Format Active Effects
        if (!empty($data['activeEffects'])) {
            $data['activeEffects'] = $this->presentEffects($data['activeEffects']);
        }

        // 2. Format Resources (Optional but cleaner)
        $data['formatted_net_worth'] = $this->abbreviateNumber($data['net_worth'] ?? 0);
        return $data;
        }
        
        /**
        * Formats a number into a shorter, more readable representation
        * (e.g., 1000 -> 1K, 1500000 -> 1.5M).
        *
        * @param int|float $number The number to format
        * @return string The abbreviated number
        */
        private function abbreviateNumber($number): string
        {
        if ($number < 1000) {
        return (string) number_format($number, 0);
        }
        
        $suffixes = ['', 'K', 'M', 'B', 'T'];
        $suffixIndex = 0;
        
        while ($number >= 1000 && $suffixIndex < count($suffixes) - 1) {
        $number /= 1000;
        $suffixIndex++;
        }
        
        return sprintf('%.1f', $number) . $suffixes[$suffixIndex];
        }
        
        /**
        * Enriches the effects array with UI properties and formatted time strings.
        */    private function presentEffects(array $effects): array
    {
        $now = new DateTime();
        $enriched = [];

        foreach ($effects as $effect) {
            // UI Mapping Logic (Moved from Controller)
            switch ($effect['effect_type']) {
                case 'jamming':
                    $effect['ui_icon'] = 'fa-satellite-dish';
                    $effect['ui_label'] = 'Radar Jamming';
                    $effect['ui_color'] = 'text-accent';
                    break;
                case 'peace_shield':
                    $effect['ui_icon'] = 'fa-shield-alt';
                    $effect['ui_label'] = 'Peace Shield';
                    $effect['ui_color'] = 'text-success';
                    break;
                case 'safehouse_cooldown':
                    $effect['ui_icon'] = 'fa-user-clock';
                    $effect['ui_label'] = 'Safehouse Cooldown';
                    $effect['ui_color'] = 'text-warning';
                    break;
                case 'safehouse_breach':
                    $effect['ui_icon'] = 'fa-key';
                    $effect['ui_label'] = 'Breach Permit';
                    $effect['ui_color'] = 'text-info';
                    break;
                case 'high_risk_protocol':
                    $effect['ui_icon'] = 'fa-biohazard';
                    $effect['ui_label'] = 'High Risk Protocol';
                    $effect['ui_color'] = 'text-danger';
                    break;
                case 'void_offense_boost':
                    $effect['ui_icon'] = 'fa-fist-raised';
                    $effect['ui_label'] = 'Adrenaline Injectors';
                    $effect['ui_color'] = 'text-success';
                    break;
                case 'void_resource_boost':
                    $effect['ui_icon'] = 'fa-industry';
                    $effect['ui_label'] = 'Nanite Overclock';
                    $effect['ui_color'] = 'text-success';
                    break;
                case 'void_defense_penalty':
                    $effect['ui_icon'] = 'fa-shield-virus';
                    $effect['ui_label'] = 'Shield Virus';
                    $effect['ui_color'] = 'text-danger';
                    break;
                case 'safehouse_block':
                    $effect['ui_icon'] = 'fa-lock';
                    $effect['ui_label'] = 'Safehouse Beacon';
                    $effect['ui_color'] = 'text-danger';
                    break;
                case 'radiation_sickness':
                    $effect['ui_icon'] = 'fa-radiation';
                    $effect['ui_label'] = 'Radiation Sickness';
                    $effect['ui_color'] = 'text-danger';
                    break;
                case 'quantum_scrambler':
                    $effect['ui_icon'] = 'fa-satellite';
                    $effect['ui_label'] = 'Quantum Scrambler';
                    $effect['ui_color'] = 'text-info';
                    break;
                case 'wounded':
                    $effect['ui_icon'] = 'fa-user-injured';
                    $effect['ui_label'] = 'Wounded';
                    $effect['ui_color'] = 'text-danger';
                    break;
                default:
                    $effect['ui_icon'] = 'fa-bolt';
                    $effect['ui_label'] = 'Unknown Effect';
                    $effect['ui_color'] = 'text-accent';
            }

            // Time Calculation Logic (Moved from View)
            $expires = new DateTime($effect['expires_at']);
            
            if ($expires <= $now) {
                // Skip expired effects if they somehow got here, or mark them
                continue; 
            }

            $interval = $now->diff($expires);
            $effect['formatted_time_left'] = $interval->format('%h hrs %i mins');

            $enriched[] = $effect;
        }

        return $enriched;
    }
}
