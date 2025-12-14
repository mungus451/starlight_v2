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
            $darkMatterCost = $costs[$key]['dark_matter'] ?? 0;

            $isMaxLevel = ($creditCost === 0 && $crystalCost === 0 && $darkMatterCost === 0); 
            $canAfford = (
                $resources->credits >= $creditCost && 
                $resources->naquadah_crystals >= $crystalCost &&
                $resources->dark_matter >= $darkMatterCost
            );

            // Format costs: "100,000 C" or "100,000 C + 5 💎" or "100,000 C + 5 🌌"
            $costParts = [number_format($creditCost) . ' C'];
            if ($crystalCost > 0) {
                $costParts[] = number_format($crystalCost) . ' 💎';
            }
            if ($darkMatterCost > 0) {
                $costParts[] = number_format($darkMatterCost) . ' 🌌';
            }
            $costFormatted = implode(' + ', $costParts);

            // 3. Determine Benefit Text (The heavy logic moved from View)
            $benefitText = $this->calculateBenefitText($key, $turnConfig, $attackConfig, $spyConfig, $currentLevel);

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

    private function calculateBenefitText(string $key, array $turnConfig, array $attackConfig, array $spyConfig, int $currentLevel): string
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
            
            case 'quantum_research_lab':
                $val = $turnConfig['research_data_per_lab_level'] ?? 0;
                return "+ " . number_format($val) . " Research Data / Turn";

            case 'nanite_forge':
                $val = ($attackConfig['nanite_casualty_reduction_per_level'] ?? 0) * 100;
                return "- " . $val . "% Casualties in Winning Battles";

            case 'dark_matter_siphon':
                $val = $turnConfig['dark_matter_per_siphon_level'] ?? 0;
                return "+ " . $val . " Dark Matter / Turn";

            case 'planetary_shield':
                $val = $attackConfig['shield_hp_per_level'] ?? 0;
                return "+ " . number_format($val) . " Shield HP";

            case 'naquadah_mining_complex':
                if ($currentLevel === 0) {
                    return "Base 10 + 1% compounding Naquadah / level";
                }
                $base = $turnConfig['naquadah_per_mining_complex_level'] ?? 0;
                $mult = $turnConfig['naquadah_production_multiplier'] ?? 1.0;
                $currentOutput = $base * $currentLevel * pow($mult, max(0, $currentLevel - 1));
                $nextOutput = $base * ($currentLevel + 1) * pow($mult, $currentLevel);
                return "Output: " . number_format($currentOutput, 2) . " / Turn (Next: " . number_format($nextOutput, 2) . ")";
            
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