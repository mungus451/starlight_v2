<?php

namespace App\Presenters;

use App\Models\Entities\BattleReport;

/**
 * Responsible for formatting Battle Reports for the View.
 * Handles "Attacker vs Defender" perspective logic, CSS classes, and outcome text.
 */
class BattleReportPresenter
{
    /**
     * Transforms a list of BattleReport entities into view-ready arrays.
     *
     * @param array $reports Array of BattleReport entities
     * @param int $viewerId The ID of the user viewing the reports
     * @return array
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
     *
     * @param BattleReport $report
     * @param int $viewerId
     * @return array
     */
    public function present(BattleReport $report, int $viewerId): array
    {
        // 1. Determine Role
        $isAttacker = ($report->attacker_id === $viewerId);
        
        // 2. Determine Outcome from Viewer's Perspective
        $isStalemate = ($report->attack_result === 'stalemate');
        $isWinner = false;

        if (!$isStalemate) {
            if ($isAttacker) {
                $isWinner = ($report->attack_result === 'victory');
            } else {
                $isWinner = ($report->attack_result === 'defeat'); // Defender wins if attack is defeat
            }
        }

        // 3. Format UI Elements based on outcome
        if ($isStalemate) {
            $statusClass = 'status-stalemate';
            $resultText = 'STALEMATE';
            $resultClass = 'draw';
            $icon = '⚖️';
            $themeClass = 'theme-draw'; // For detail view
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

        // 4. Format Names and Roles
        $opponentName = $isAttacker ? $report->defender_name : $report->attacker_name;
        $opponentName = $opponentName ?? 'Unknown Target';
        
        $viewerName = $isAttacker ? $report->attacker_name : $report->defender_name;
        $viewerName = $viewerName ?? 'Unknown';

        $roleText = $isAttacker ? 'Attacker' : 'Defender';
        $roleClass = $isAttacker ? 'role-attacker' : 'role-defender';
        
        $viewerLabel = $isAttacker ? 'Attacker' : 'Defender';
        $opponentLabel = $isAttacker ? 'Defender' : 'Attacker';

        // 5. Format Relative Value Text (vs opponent)
        $verb = $isAttacker ? 'vs' : 'from';

        return [
            'id' => $report->id,
            'created_at' => $report->created_at,
            'formatted_date' => date('M d, H:i', strtotime($report->created_at)),
            'full_date' => date('F j, Y - H:i', strtotime($report->created_at)),
            
            // Logic Booleans
            'is_attacker' => $isAttacker,
            'is_winner' => $isWinner,
            'is_stalemate' => $isStalemate,
            
            // Display Text
            'opponent_name' => $opponentName,
            'viewer_name' => $viewerName,
            'role_text' => $roleText,
            'result_text' => $resultText,
            'versus_text' => $verb . ' ' . $opponentName,
            'viewer_label' => $viewerLabel,
            'opponent_label' => $opponentLabel,
            
            // CSS Classes
            'status_class' => $statusClass,
            'result_class' => $resultClass,
            'role_class' => $roleClass,
            'theme_class' => $themeClass,
            'icon' => $icon,
            
            // Raw Data (Passed through for detail view specifics)
            'credits_plundered' => $report->credits_plundered,
            'experience_gained' => $report->experience_gained,
            'soldiers_sent' => $report->soldiers_sent,
            'attacker_offense_power' => $report->attacker_offense_power,
            'defender_defense_power' => $report->defender_defense_power,
            'attacker_soldiers_lost' => $report->attacker_soldiers_lost,
            'defender_guards_lost' => $report->defender_guards_lost,
        ];
    }
}