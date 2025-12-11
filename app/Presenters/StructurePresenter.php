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
        $turnConfig = $data['turnConfig'];
        $attackConfig = $data['attackConfig'];
        $spyConfig = $data['spyConfig'];

        $grouped = [];
        $categoryOrder = ['Economy', 'Defense', 'Offense', 'Intel'];

        foreach ($structureFormulas as $key => $details) {
            $category = $details['category'] ?? 'Uncategorized';
            
            // 1. Determine Levels
            $columnName = $key . '_level';
            $currentLevel = $structures->{$columnName} ?? 0;
            $nextLevel = $currentLevel + 1;
            
            // 2. Determine Costs & Status
            $creditCost = $costs[$key]['credits'] ?? 0;
            $crystalCost = $costs[$key]['crystals'] ?? 0;

            $isMaxLevel = ($creditCost === 0 && $crystalCost === 0); 
            $canAfford = (
                $resources->credits >= $creditCost && 
                $resources->naquadah_crystals >= $crystalCost
            );

            // Format costs: "100,000 C" or "100,000 C + 5 💎"
            $costFormatted = number_format($creditCost) . ' C';
            if ($crystalCost > 0) {
                $costFormatted .= ' + ' . number_format($crystalCost) . ' 💎';
            }

            // 3. Determine Benefit Text (The heavy logic moved from View)
            $benefitText = $this->calculateBenefitText($key, $turnConfig, $attackConfig, $spyConfig);

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
                'upgrade_cost_crystals' => $crystalCost, // Keep for raw access if needed
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

    private function calculateBenefitText(string $key, array $turnConfig, array $attackConfig, array $spyConfig): string
    {
        switch ($key) {
            case 'economy_upgrade':
                $val = $turnConfig['credit_income_per_econ_level'] ?? 0;
                return "+ " . number_format($val) . " Credits / Turn";
            
            case 'population':
                $val = $turnConfig['citizen_growth_per_pop_level'] ?? 0;
                return "+ " . number_format($val) . " Citizens / Turn";
            
            case 'offense_upgrade':
                $val = ($attackConfig['power_per_offense_level'] ?? 0) * 100;
                return "+ " . $val . "% Soldier Power";
            
            case 'fortification':
                $val = ($attackConfig['power_per_fortification_level'] ?? 0) * 100;
                return "+ " . $val . "% Guard Power";
            
            case 'defense_upgrade':
                $val = ($attackConfig['power_per_defense_level'] ?? 0) * 100;
                return "+ " . $val . "% Defense Power";
            
            case 'spy_upgrade':
                $val = ($spyConfig['offense_power_per_level'] ?? 0) * 100;
                return "+ " . $val . "% Spy/Sentry Power";
            
            case 'armory':
                return "Unlocks & Upgrades Item Tiers";

            case 'accounting_firm':
                return "+ 1% Global Income Multiplier / Level";
            
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
            default   => '⚙️',
        };
    }
}