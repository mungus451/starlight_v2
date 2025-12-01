<?php

namespace App\Presenters;

use App\Models\Entities\BattleReport;

/**
* Responsible for formatting Battle Reports for the View.
* Updated to handle Anonymous (Shadow) Contracts.
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
// 1. Determine Role
$isAttacker = ($report->attacker_id === $viewerId);

// 2. Name Masking Logic (Shadow Contracts)
// Default to real names
$attackerDisplayName = $report->attacker_name ?? 'Unknown';

if ($report->is_hidden) {
if ($isAttacker) {
// If viewer IS the attacker, they know it was them, but mark it clearly
$attackerDisplayName .= " (Shadow Ops)";
} else {
// If viewer is the defender (or 3rd party admin), mask the name
$attackerDisplayName = "The Void Syndicate";
}
}

// 3. Determine Outcome
$isStalemate = ($report->attack_result === 'stalemate');
$isWinner = false;

if (!$isStalemate) {
if ($isAttacker) {
$isWinner = ($report->attack_result === 'victory');
} else {
$isWinner = ($report->attack_result === 'defeat'); // Defender wins if attacker lost
}
}

// 4. Format UI Elements
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

// 5. Assign Labels based on perspective
$opponentName = $isAttacker ? ($report->defender_name ?? 'Unknown Target') : $attackerDisplayName;
$viewerName = $isAttacker ? $attackerDisplayName : ($report->defender_name ?? 'Unknown');

$roleText = $isAttacker ? 'Attacker' : 'Defender';
$roleClass = $isAttacker ? 'role-attacker' : 'role-defender';

$viewerLabel = $isAttacker ? 'Attacker' : 'Defender';
$opponentLabel = $isAttacker ? 'Defender' : 'Attacker';

// Verb for "Battle vs X" or "Battle from X"
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
'is_hidden' => $report->is_hidden,

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
}