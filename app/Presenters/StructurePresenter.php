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
        
        $grouped = [];
        $categoryOrder = ['Economy', 'Population', 'Armory', 'Mercenary', 'Military', 'Defense', 'Intel', 'Advanced Industry', 'Super Defense'];

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
            $costFormatted = $isMaxLevel ? '' : number_format($creditCost) . ' C';

            // Set max_level for display
            $maxLevel = $isMaxLevel ? $currentLevel : ($currentLevel + 1); 
            
            // Nullify cost if max level to differentiate from actual zero cost
            if ($isMaxLevel) {
                $creditCost = null;
            }

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
                'max_level' => $maxLevel, // Ensure max_level is always present
                'next_level' => $nextLevel,
                'upgrade_cost_credits' => $creditCost, 
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
            'Economy' => '<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
  <circle cx="50" cy="50" r="45" fill="#1a1a1a" stroke="#d4af37" stroke-width="2" />
  
  <circle cx="50" cy="50" r="40" fill="#2c2c2c" />
  
  <path d="M30 30 H70 M30 50 H70 M30 70 H70 M50 30 V70" stroke="#d4af37" stroke-width="0.5" opacity="0.4" />
  <rect x="35" y="35" width="5" height="5" fill="#d4af37" opacity="0.6" />
  <rect x="60" y="60" width="5" height="5" fill="#d4af37" opacity="0.6" />
  
  <text x="50" y="58" font-family="monospace" font-weight="bold" font-size="28" fill="#ffcc00" text-anchor="middle" style="text-shadow: 0 0 8px #ffaa00;">
    10
  </text>
  
  <circle cx="50" cy="50" r="40" fill="none" stroke="#ffcc00" stroke-width="1">
    <animate attributeName="r" from="40" to="44" dur="2s" repeatCount="indefinite" />
    <animate attributeName="opacity" from="0.5" to="0" dur="2s" repeatCount="indefinite" />
  </circle>
</svg>',
            'Population' => '<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <radialGradient id="pulse-glow" cx="50%" cy="50%" r="50%">
      <stop offset="0%" stop-color="#00ffcc" stop-opacity="0.2" />
      <stop offset="100%" stop-color="#00ffcc" stop-opacity="0" />
    </radialGradient>
  </defs>
  <circle cx="50" cy="50" r="45" fill="url(#pulse-glow)" />

  <path d="M50 30 L55 35 L55 42 L45 42 L45 35 Z" fill="#00ffcc" /> <path d="M38 70 L42 45 L58 45 L62 70 Z" fill="#00ffcc" /> <path d="M30 40 A 30 30 0 0 1 70 40" stroke="#00ffcc" stroke-width="2" fill="none" stroke-linecap="round" opacity="0.8" />
  <path d="M20 35 A 40 40 0 0 1 80 35" stroke="#00ffcc" stroke-width="1.5" fill="none" stroke-linecap="round" opacity="0.5" />
  <path d="M10 30 A 55 55 0 0 1 90 30" stroke="#00ffcc" stroke-width="1" fill="none" stroke-linecap="round" opacity="0.2" />

  <circle cx="50" cy="50" r="10" fill="none" stroke="#00ffcc" stroke-width="2">
    <animate attributeName="r" from="10" to="45" dur="1.5s" repeatCount="indefinite" />
    <animate attributeName="opacity" from="0.8" to="0" dur="1.5s" repeatCount="indefinite" />
  </circle>
</svg>',
            'Armory' => '<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
  <path d="M20 35 V20 H35" stroke="#ff3300" stroke-width="3" fill="none" />
  <path d="M65 20 H80 V35" stroke="#ff3300" stroke-width="3" fill="none" />
  <path d="M80 65 V80 H65" stroke="#ff3300" stroke-width="3" fill="none" />
  <path d="M35 80 H20 V65" stroke="#ff3300" stroke-width="3" fill="none" />

  <circle cx="50" cy="50" r="25" stroke="#ff3300" stroke-width="1.5" fill="none" stroke-dasharray="8,4" opacity="0.6">
    <animateTransform attributeName="transform" type="rotate" from="0 50 50" to="360 50 50" dur="10s" repeatCount="indefinite" />
  </circle>

  <path d="M45 50 H55 M50 45 V55" stroke="#ff3300" stroke-width="2" fill="none" />
  
  <rect x="48" y="48" width="4" height="4" fill="#ff3300">
    <animate attributeName="opacity" values="1;0.2;1" dur="0.8s" repeatCount="indefinite" />
  </rect>
</svg>',
            'Mercenary' => '<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
  <path d="M25 60 L75 35 L80 45 L30 70 Z" fill="#d4af37" stroke="#b8860b" stroke-width="1" />
  <path d="M25 60 L75 35 L76 38 L28 62 Z" fill="#ffd700" opacity="0.5" />

  <path d="M40 95 L35 75 L65 75 L60 95 Z" fill="#7a00cc" stroke="#9d00ff" stroke-width="2" />
  
  <rect x="35" y="45" width="8" height="20" rx="2" transform="rotate(-15 35 45)" fill="#9d00ff" stroke="#1a1a1a" stroke-width="1" />
  <rect x="46" y="40" width="8" height="22" rx="2" transform="rotate(-15 46 40)" fill="#9d00ff" stroke="#1a1a1a" stroke-width="1" />
  <rect x="57" y="38" width="8" height="22" rx="2" transform="rotate(-15 57 38)" fill="#9d00ff" stroke="#1a1a1a" stroke-width="1" />
  <rect x="68" y="40" width="8" height="18" rx="2" transform="rotate(-15 68 40)" fill="#9d00ff" stroke="#1a1a1a" stroke-width="1" />

  <path d="M30 75 L20 60 L28 55 L38 70 Z" fill="#9d00ff" stroke="#1a1a1a" stroke-width="1" />

  <circle cx="50" cy="85" r="2" fill="#00ffff">
    <animate attributeName="opacity" values="1;0.3;1" dur="1.5s" repeatCount="indefinite" />
  </circle>
</svg>',
            'Defense' => '🛡️',
            'Offense' => '⚔️',
            'Intel'   => '📡',
            'Military' => '🛡️',
            'Advanced Industry' => '🔬',
            'Super Defense' => '<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                  <circle cx="50" cy="50" r="20" fill="#1a1a1a" stroke="#00e5ff" stroke-width="1" />
                  <circle cx="50" cy="50" r="15" fill="#00e5ff" opacity="0.2" />

                  <circle cx="50" cy="50" r="40" fill="none" stroke="#00e5ff" stroke-width="2" stroke-dasharray="20, 10" opacity="0.8">
                    <animateTransform attributeName="transform" type="rotate" from="0 50 50" to="360 50 50" dur="4s" repeatCount="indefinite" />
                  </circle>

                  <circle cx="50" cy="50" r="32" fill="none" stroke="#00e5ff" stroke-width="1.5" stroke-dasharray="15, 15" opacity="0.5">
                    <animateTransform attributeName="transform" type="rotate" from="360 50 50" to="0 50 50" dur="8s" repeatCount="indefinite" />
                  </circle>

                  <path d="M45 10 Q50 5 55 10" fill="none" stroke="#ffffff" stroke-width="1" opacity="0.6">
                    <animate attributeName="opacity" values="0;1;0" dur="0.5s" repeatCount="indefinite" />
                  </path>
                  <path d="M85 45 Q90 50 85 55" fill="none" stroke="#ffffff" stroke-width="1" opacity="0.6">
                    <animate attributeName="opacity" values="0;1;0" dur="0.7s" begin="0.2s" repeatCount="indefinite" />
                  </path>
                </svg>',
            default   => '⚙️',
        };
    }
}