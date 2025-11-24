<?php

namespace App\Presenters;

use App\Models\Entities\SpyReport;

/**
 * Responsible for formatting Spy Reports for the View.
 * Handles the logic of "Success" vs "Failure" which means different things
 * depending on if you are the Attacker (Operator) or Defender (Target).
 */
class SpyReportPresenter
{
    /**
     * Transforms a list of SpyReport entities into view-ready arrays.
     *
     * @param array $reports Array of SpyReport entities
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
     * Transforms a single SpyReport entity into a view-ready array.
     *
     * @param SpyReport $report
     * @param int $viewerId
     * @return array
     */
    public function present(SpyReport $report, int $viewerId): array
    {
        // 1. Determine Role
        $isAttacker = ($report->attacker_id === $viewerId);

        // 2. Names & Labels
        $viewerName = $isAttacker ? $report->attacker_name : $report->defender_name;
        $viewerName = $viewerName ?? 'Unknown';
        
        $opponentName = $isAttacker ? $report->defender_name : $report->attacker_name;
        $opponentName = $opponentName ?? 'Unknown Target';

        $viewerLabel = $isAttacker ? 'Operator' : 'Target';
        $opponentLabel = $isAttacker ? 'Target' : 'Operator';
        
        // 3. Determine Outcome & Theming
        // 'success' in DB means the spy got the intel.
        // 'failure' in DB means the spy failed to get intel (and usually got caught).
        
        if ($isAttacker) {
            // Attacker Perspective
            $operationSuccess = ($report->operation_result === 'success');
            $resultText = $operationSuccess ? 'OPERATION SUCCESSFUL' : 'OPERATION FAILED';
            $shortResultText = $operationSuccess ? 'Success' : 'Failed';
            
            $themeClass = $operationSuccess ? 'theme-win' : 'theme-loss';
            $statusClass = $operationSuccess ? 'status-victory' : 'status-defeat';
            $resultClass = $operationSuccess ? 'res-win' : 'res-loss';
            $icon = $operationSuccess ? '📡' : '⚠️';
            $verb = 'vs';
            
        } else {
            // Defender Perspective
            // If operation_result is 'success', the defender was breached (Loss).
            // If operation_result is 'failure', the defender caught the spy (Win).
            $operationSuccess = ($report->operation_result !== 'success'); // Inverted for Defender logic
            $resultText = $operationSuccess ? 'SPY NEUTRALIZED' : 'SECURITY BREACH';
            $shortResultText = $operationSuccess ? 'Success (Caught)' : 'Failed (Breached)';
            
            $themeClass = $operationSuccess ? 'theme-win' : 'theme-loss';
            $statusClass = $operationSuccess ? 'status-victory' : 'status-defeat';
            $resultClass = $operationSuccess ? 'res-win' : 'res-loss';
            $icon = $operationSuccess ? '🛡️' : '🚨';
            $verb = 'from';
        }

        return [
            'id' => $report->id,
            'created_at' => $report->created_at,
            'formatted_date' => date('M j, H:i', strtotime($report->created_at)),
            'full_date' => date('F j, Y - H:i', strtotime($report->created_at)),
            
            'is_attacker' => $isAttacker,
            'operation_result' => $report->operation_result, // Raw DB value needed for conditional blocks
            
            'viewer_name' => $viewerName,
            'opponent_name' => $opponentName,
            'viewer_label' => $viewerLabel,
            'opponent_label' => $opponentLabel,
            
            'result_text' => $resultText,
            'short_result_text' => $shortResultText,
            'versus_text' => $verb . ' ' . $opponentName,
            'role_text' => $viewerLabel,
            'role_class' => $isAttacker ? 'role-attacker' : 'role-defender',
            
            'theme_class' => $themeClass,
            'status_class' => $statusClass,
            'result_class' => $resultClass,
            'icon' => $icon,
            
            // Raw Data Pass-through
            'spies_lost_attacker' => $report->spies_lost_attacker,
            'sentries_lost_defender' => $report->sentries_lost_defender,
            
            // Intel Data
            'credits_seen' => $report->credits_seen,
            'soldiers_seen' => $report->soldiers_seen,
            'guards_seen' => $report->guards_seen,
            'sentries_seen' => $report->sentries_seen,
            'fortification_level_seen' => $report->fortification_level_seen,
            'armory_level_seen' => $report->armory_level_seen,
        ];
    }
}