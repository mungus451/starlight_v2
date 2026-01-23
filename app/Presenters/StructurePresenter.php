<?php

namespace App\Presenters;

use App\Models\Entities\UserStructure;

/**
 * Responsible for formatting Structure data for the View.
 * Moves presentation logic (icons, descriptions, specific benefit calculations) out of the HTML.
 */
class StructurePresenter
{
    /**
     * Transforms raw service data into a view-ready array.
     *
     * @param array $data The array returned from StructureService::getStructureData
     * @return array Grouped and formatted structure data
     */
    public function present(array $data): array
    {
        $structureFormulas = $data['structureFormulas'] ?? [];
        $structures = $data['structures']; // UserStructure Entity
        $resources = $data['resources'];   // UserResource Entity
        $costs = $data['costs'];
        
        // Configs for benefit calculations
        $turnConfig = $data['turnConfig'] ?? [];
        $attackConfig = $data['attackConfig'] ?? [];
        $spyConfig = $data['spyConfig'] ?? [];
        // New configs might be needed, but we can pass them via $data if we update the service.
        // For now, we assume key values are available in the main config arrays or generic $data['game_balance'] if we passed it.
        // Actually, StructureService usually passes specific config sections.
        // Let's rely on what's typically available or hardcode constants if they aren't passed yet.
        // Note: TurnProcessorService logic was updated to use specific keys, we should try to match that.
        
        // We might need to access the full game_balance array for the new structures if they aren't in turn/attack/spy config subsets passed by the service.
        // But for safety, I'll check if keys exist in the passed arrays, otherwise fall back to 0 or hardcoded logic for display.

        $grouped = [];
        $categoryOrder = ['Economy', 'Military', 'Defense', 'Intel', 'Advanced Industry', 'Super Defense'];

        foreach ($structureFormulas as $key => $details) {
            $category = $details['category'] ?? 'Uncategorized';
            
            // Remap Offense to Military for frontend consistency
            if ($category === 'Offense') {
                $category = 'Military';
            }
            
            // 1. Determine Levels
            $columnName = $key . '_level';
            $currentLevel = $structures->{$columnName} ?? 0;
            $nextLevel = $currentLevel + 1;
            
            // 2. Determine Costs & Status
            $creditCost = $costs[$key]['credits'] ?? 0;

            $isMaxLevel = ($creditCost === 0); 
            $canAfford = (
                $resources->credits >= $creditCost
            );

            // Format costs: "100,000 C"
            $costFormatted = number_format($creditCost) . ' C';

            // 3. Determine Benefit Text (The heavy logic moved from View)
            $benefitText = $this->calculateBenefitText($key, $data, $currentLevel);

            // 4. Determine Icon
            $icon = $this->getCategoryIcon($category);

            // 5. Build ViewModel
            $viewModel = [
                'key' => $key,
                'name' => $details['name'] ?? 'Unknown',
                'description' => $details['description'] ?? '',
                'current_level' => $currentLevel,
                'next_level' => $nextLevel,
                'upgrade_cost_credits' => $creditCost, // Keep for raw access if needed
                'cost_formatted' => $costFormatted,
                'is_max_level' => $isMaxLevel,
                'can_afford' => $canAfford,
                'benefit_text' => $benefitText,
                'icon' => $icon,
                'status_class' => $canAfford ? 'affordable' : 'insufficient'
            ];

            $grouped[$category][] = $viewModel;
        }

        // Ensure strictly ordered categories
        $orderedGrouped = [];
        foreach ($categoryOrder as $cat) {
            if (isset($grouped[$cat])) {
                $orderedGrouped[$cat] = $grouped[$cat];
            }
        }
        // Add any remaining categories
        foreach ($grouped as $cat => $items) {
            if (!in_array($cat, $categoryOrder)) {
                $orderedGrouped[$cat] = $items;
            }
        }

        return $orderedGrouped;
    }

    private function calculateBenefitText(string $key, array $data, int $currentLevel): string
    {
        // Extract configs from data wrapper for easier access
        $turnConfig = $data['turnConfig'] ?? [];
        $attackConfig = $data['attackConfig'] ?? [];
        $spyConfig = $data['spyConfig'] ?? [];
        
        // For new structures, we might need to look at specific sections if available
        // But we can mostly hardcode the display logic based on standard formulas if the exact config isn't passed to the view model yet.
        // Ideally, StructureService should pass the full game_balance or the relevant subsections.
        
        switch ($key) {
            case 'economy_upgrade':
                $val = $turnConfig['credit_income_per_econ_level'] ?? 0;
                return "+ " . number_format($val) . " Credits / Turn";
            
            case 'population':
                $val = $turnConfig['citizen_growth_per_pop_level'] ?? 0;
                return "+ " . number_format($val) . " Citizens / Turn";
            
            case 'armory':
                return "Unlocks & Upgrades Item Tiers";

            case 'planetary_shield':
                $val = $attackConfig['shield_hp_per_level'] ?? 0;
                return "+ " . number_format($val) . " Shield HP";

            // --- NEW EXPANSION STRUCTURES ---
            case 'mercenary_outpost':
                return "Unlocks Emergency Draft";

            case 'neural_uplink':
                // 2% per level
                $val = ($spyConfig['neural_uplink_bonus_per_level'] ?? 0.02) * 100;
                return "+ " . number_format($val, 0) . "% Sentry Counter-Ops";

            case 'subspace_scanner':
                return "Improves Incoming Attack Intel";
            
            default:
                return "";
        }
    }

    private function getCategoryIcon(string $category): string
    {
        return match ($category) {
            'Economy' => '💰',
            'Defense' => '🛡️',
            'Offense' => '⚔️',
            'Intel'   => '📡',
            'Military' => '🛡️',
            'Advanced Industry' => '🔬',
            'Super Defense' => '✨',
            default   => '⚙️',
        };
    }
}
