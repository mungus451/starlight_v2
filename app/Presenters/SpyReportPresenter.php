<?php

namespace App\Presenters;

use App\Models\Entities\SpyReport;

/**
 * Responsible for formatting Spy Reports for the View.
 * Updated to include narrative generation.
 */
class SpyReportPresenter
{
    /**
     * Transforms a list of SpyReport entities into view-ready arrays.
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
            // Defender Perspective (Success means Spy Caught)
            $operationSuccess = ($report->operation_result !== 'success');
            $resultText = $operationSuccess ? 'SPY NEUTRALIZED' : 'SECURITY BREACH';
            $shortResultText = $operationSuccess ? 'Success (Caught)' : 'Failed (Breached)';
            
            $themeClass = $operationSuccess ? 'theme-win' : 'theme-loss';
            $statusClass = $operationSuccess ? 'status-victory' : 'status-defeat';
            $resultClass = $operationSuccess ? 'res-win' : 'res-loss';
            $icon = $operationSuccess ? '🛡️' : '🚨';
            $verb = 'from';
        }

        // 4. Generate Narrative
        $storyHtml = $this->generateNarrative($viewerName, $opponentName, $report, $isAttacker);

        return [
            'id' => $report->id,
            'created_at' => $report->created_at,
            'formatted_date' => date('M j, H:i', strtotime($report->created_at)),
            'full_date' => date('F j, Y - H:i', strtotime($report->created_at)),
            
            'is_attacker' => $isAttacker,
            'operation_result' => $report->operation_result,
            
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
            
            'story_html' => $storyHtml,

            // Raw Data
            'spies_lost_attacker' => $report->spies_lost_attacker,
            'sentries_lost_defender' => $report->sentries_lost_defender,
            'credits_seen' => $report->credits_seen,
            'soldiers_seen' => $report->soldiers_seen,
            'guards_seen' => $report->guards_seen,
            'sentries_seen' => $report->sentries_seen,
            'fortification_level_seen' => $report->fortification_level_seen,
            'armory_level_seen' => $report->armory_level_seen,
        ];
    }

    /**
     * Generates narrative text for the report.
     */
    private function generateNarrative(string $viewerName, string $opponentName, SpyReport $report, bool $isAttacker): string
    {
        $fmt = fn($n) => number_format($n);
        
        $atkName = $isAttacker ? $viewerName : $opponentName;
        $defName = $isAttacker ? $opponentName : $viewerName;

        // Line 1: Deployment
        // Handle legacy reports where total_sentries might be 0
        $sentriesText = $report->defender_total_sentries > 0 ? $fmt($report->defender_total_sentries) : "an unknown number of";
        $line1 = "<strong>{$atkName}</strong> deployed {$fmt($report->spies_sent)} spies to infiltrate <strong>{$defName}</strong>'s network, defended by {$sentriesText} sentries.";

        // Line 2: Outcome
        $isSuccess = ($report->operation_result === 'success');
        $outcomeColor = $isSuccess ? 'var(--accent-blue)' : 'var(--accent-red)';
        $outcomeText = $isSuccess ? "successfully breached security protocols" : "was detected by counter-intelligence";
        $line2 = "The operation <strong>{$outcomeText}</strong>.";

        // Line 3: Casualties
        $line3 = "Casualties: <span class='val-danger'>{$fmt($report->spies_lost_attacker)}</span> spies lost vs <span class='val-danger'>{$fmt($report->sentries_lost_defender)}</span> sentries destroyed.";

        // Line 4: Result Detail
        $line4 = "";
        if ($isSuccess) {
            $line4 = "Full intelligence dossier downloaded.";
        } else {
            $line4 = "Connection terminated. No data retrieved.";
        }

        return "
            <p>{$line1}</p>
            <p style='color: {$outcomeColor}'>{$line2}</p>
            <p>{$line3}</p>
            <p>{$line4}</p>
        ";
    }
}