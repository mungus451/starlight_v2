<?php

namespace App\Presenters;

use App\Models\Entities\BattleReport;

/**
 * Responsible for formatting Battle Reports for the View.
 * Handles narrative generation and UI theming.
 */
class BattleReportPresenter
{
    /**
     * Transforms a list of BattleReport entities into view-ready arrays.
     */
    public function presentAll(array $reports, int $viewerId): array
    {
        $presented = [];
        foreach ($reports as $report) {
            $presented[] = $this->present($report, $viewerId);
        }
        return $presented;
    }

    /**
     * Transforms a single BattleReport entity into a view-ready array.
     */
    public function present(BattleReport $report, int $viewerId): array
    {
        // 1. Determine Role & Names
        $isAttacker = ($report->attacker_id === $viewerId);
        
        $attackerNameReal = $report->attacker_name ?? 'Unknown';
        $attackerDisplayName = $report->is_hidden 
            ? ($isAttacker ? $attackerNameReal . " (Shadow Ops)" : "The Void Syndicate") 
            : $attackerNameReal;
            
        $defenderName = $report->defender_name ?? 'Unknown Target';

        // 2. Determine Outcome
        $isStalemate = ($report->attack_result === 'stalemate');
        $isWinner = false;
        if (!$isStalemate) {
            $isWinner = $isAttacker ? ($report->attack_result === 'victory') : ($report->attack_result === 'defeat');
        }

        // 3. Format UI Elements
        if ($isStalemate) {
            $statusClass = 'status-stalemate';
            $resultText = 'STALEMATE';
            $resultClass = 'draw';
            $icon = '⚖️';
            $themeClass = 'theme-draw';
        } elseif ($isWinner) {
            $statusClass = 'status-victory';
            $resultText = 'VICTORY';
            $resultClass = 'win';
            $icon = '🏆';
            $themeClass = 'theme-win';
        } else {
            $statusClass = 'status-defeat';
            $resultText = 'DEFEAT';
            $resultClass = 'loss';
            $icon = '💀';
            $themeClass = 'theme-loss';
        }

        // 4. Generate Narrative HTML
        $storyHtml = $this->generateNarrative(
            $attackerDisplayName, 
            $defenderName, 
            $report, 
            $isAttacker
        );

        // 5. Labels & Names
        $viewerName = $isAttacker ? $attackerDisplayName : $defenderName;
        $opponentName = $isAttacker ? $defenderName : $attackerDisplayName;
        
        $roleText = $isAttacker ? 'Attacker' : 'Defender';
        $roleClass = $isAttacker ? 'role-attacker' : 'role-defender';
        
        // --- FIXED: Explicitly define these variables for the View ---
        $viewerLabel = $isAttacker ? 'Attacker' : 'Defender';
        $opponentLabel = $isAttacker ? 'Defender' : 'Attacker';
        // -------------------------------------------------------------

        $verb = $isAttacker ? 'vs' : 'from';

        return [
            'id' => $report->id,
            'created_at' => $report->created_at,
            'formatted_date' => date('M d, H:i', strtotime($report->created_at)),
            'full_date' => date('F j, Y - H:i', strtotime($report->created_at)),
            
            'is_attacker' => $isAttacker,
            'is_winner' => $isWinner,
            'is_stalemate' => $isStalemate,
            'is_hidden' => $report->is_hidden,
            
            'opponent_name' => $opponentName,
            'viewer_name' => $viewerName,
            
            'role_text' => $roleText,
            'viewer_label' => $viewerLabel,     // Fixed
            'opponent_label' => $opponentLabel, // Fixed
            
            'result_text' => $resultText,
            'versus_text' => $verb . ' ' . $opponentName,
            
            'status_class' => $statusClass,
            'result_class' => $resultClass,
            'role_class' => $roleClass,
            'theme_class' => $themeClass,
            'icon' => $icon,
            
            'story_html' => $storyHtml,

            // Raw Data
            'credits_plundered' => $report->credits_plundered,
            'experience_gained' => $report->experience_gained,
            'soldiers_sent' => $report->soldiers_sent,
            'attacker_offense_power' => $report->attacker_offense_power,
            'defender_defense_power' => $report->defender_defense_power,
            'attacker_soldiers_lost' => $report->attacker_soldiers_lost,
            'defender_guards_lost' => $report->defender_guards_lost,
        ];
    }

    /**
     * Generates the story-style HTML for the report.
     */
    private function generateNarrative(string $atkName, string $defName, BattleReport $report, bool $isAttackerPerspective): string
    {
        // Helper for formatting
        $fmt = fn($n) => number_format($n);
        $kFmt = fn($n) => number_format($n); // Updated to full number format

        // Line 1: Deployment
        $line1 = "<strong>{$atkName}</strong> sent {$fmt($report->soldiers_sent)} soldiers with {$kFmt($report->attacker_offense_power)} offense. ";
        // Handle legacy reports where total_guards might be 0
        $defGuardsText = $report->defender_total_guards > 0 ? $fmt($report->defender_total_guards) : "an army of";
        $line1 .= "<strong>{$defName}</strong> had {$defGuardsText} guards with {$kFmt($report->defender_defense_power)} defense.";

        // Line 2: Outcome
        $outcomeWord = strtoupper($report->attack_result); // VICTORY / DEFEAT / STALEMATE
        $outcomeColor = match($report->attack_result) {
            'victory' => 'var(--accent-green)',
            'defeat' => 'var(--accent-red)',
            'stalemate' => 'var(--accent-2)',
            default => 'var(--text)'
        };
        
        $line2 = "{$atkName}'s attack was <strong style='color:{$outcomeColor}'>{$outcomeWord}</strong> against {$defName}.";

        // Line 3: Casualties
        $line3 = "{$atkName} suffered <span class='val-danger'>{$fmt($report->attacker_soldiers_lost)}</span> casualties. ";
        $line3 .= "{$defName} suffered <span class='val-danger'>{$fmt($report->defender_guards_lost)}</span> casualties.";

        // Line 4: Economic Impact (Only if meaningful)
        $line4 = "";
        if ($report->attack_result === 'victory') {
            $line4 .= "<br>{$atkName} pillages <span class='val-success'>{$fmt($report->credits_plundered)}</span> credits.";
            if ($report->net_worth_stolen > 0) {
                $line4 .= "<br>{$atkName} caused <span class='text-accent'>{$fmt($report->net_worth_stolen)}</span> foundation damage.";
            }
        } elseif ($report->attack_result === 'defeat') {
            $line4 .= "<br>Defenses held. No resources were stolen.";
        }

        return "
            <p>{$line1}</p>
            <p>{$line2}</p>
            <p>{$line3}</p>
            <p>{$line4}</p>
        ";
    }
}