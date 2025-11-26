<?php

namespace App\Presenters;

/**
 * Responsible for formatting Armory data for the View.
 * Handles visual logic like CSS classes, button text, and badge formatting.
 */
class ArmoryPresenter
{
    /**
     * Transforms raw service data into a view-ready array.
     *
     * @param array $data The array returned from ArmoryService::getArmoryData
     * @return array The ViewModel with enriched presentation data
     */
    public function present(array $data): array
    {
        // We only need to enrich the manufacturing data structure
        // Other keys (userResources, etc.) are passed through, but we rebuild the main array to be safe
        
        $manufacturingData = $data['manufacturingData'] ?? [];
        $enrichedManufacturing = [];

        foreach ($manufacturingData as $unitKey => $tiers) {
            foreach ($tiers as $tier => $items) {
                foreach ($items as $item) {
                    $enrichedManufacturing[$unitKey][$tier][] = $this->presentItem($item);
                }
            }
        }

        // Return the full data array with the modified manufacturing section
        $data['manufacturingData'] = $enrichedManufacturing;
        
        return $data;
    }

    /**
     * Formats a single item's display properties.
     */
    private function presentItem(array $item): array
    {
        // 1. Status Classes
        $item['level_status_class'] = $item['has_level'] ? 'status-ok' : 'status-bad';
        
        // 2. Button Labels
        $item['manufacture_btn_text'] = $item['is_tier_1'] ? 'Manufacture' : 'Upgrade';

        // 3. Formatted Cost
        $item['base_cost_formatted'] = number_format($item['base_cost']);
        $item['effective_cost_formatted'] = number_format($item['effective_cost']);
        $item['current_owned_formatted'] = number_format($item['current_owned']);
        $item['prereq_owned_formatted'] = number_format($item['prereq_owned']);

        // 4. Stat Badges
        // Service now passes raw stats; we format them into badges here.
        $badges = [];
        if (isset($item['attack']) && $item['attack'] > 0) {
            $badges[] = ['type' => 'attack', 'label' => "+{$item['attack']} Atk"];
        }
        if (isset($item['defense']) && $item['defense'] > 0) {
            $badges[] = ['type' => 'defense', 'label' => "+{$item['defense']} Def"];
        }
        if (isset($item['credit_bonus']) && $item['credit_bonus'] > 0) {
            $badges[] = ['type' => 'defense', 'label' => "+{$item['credit_bonus']} Cr"]; 
        }
        $item['stat_badges'] = $badges;

        return $item;
    }
}