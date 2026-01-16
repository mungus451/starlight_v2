<?php

namespace App\Presenters;

use App\Core\Permissions;
use App\Models\Entities\Alliance;
use App\Models\Entities\User;
use App\Models\Entities\AllianceRole;
use DateTime;

/**
* Prepares data for the Alliance Profile view.
* Removes all conditional logic, date formatting, and permission checks from the HTML.
*/
class AllianceProfilePresenter
{
/**
* Transform the raw service data into a view-safe array.
*
* @param array $data The array returned by AllianceService::getPublicProfileData
* @return array The fully formatted ViewModel
*/
public function present(array $data): array
{
/** @var Alliance $alliance */
$alliance = $data['alliance'];
/** @var User $viewer */
$viewer = $data['viewer'];
/** @var AllianceRole|null $viewerRole */
$viewerRole = $data['viewerRole'];

// 1. Determine High-Level User State
$isMember = ($viewer->alliance_id === $alliance->id);
$isLeader = ($viewerRole && $viewerRole->name === 'Leader');
$hasApplied = ($data['userApplication'] !== null);

// 2. Calculate Global Permissions (Booleans for the View)
$permissions = [
'can_edit_profile' => $viewerRole && $viewerRole->hasPermission(Permissions::CAN_EDIT_PROFILE),
'can_manage_apps' => $viewerRole && $viewerRole->hasPermission(Permissions::CAN_MANAGE_APPLICATIONS),
'can_invite' => $viewerRole && $viewerRole->hasPermission(Permissions::CAN_INVITE_MEMBERS),
'can_kick' => $viewerRole && $viewerRole->hasPermission(Permissions::CAN_KICK_MEMBERS),
'can_manage_roles' => $viewerRole && $viewerRole->hasPermission(Permissions::CAN_MANAGE_ROLES),
'can_manage_bank' => $viewerRole && $viewerRole->hasPermission(Permissions::CAN_MANAGE_BANK),
'can_manage_structures' => $viewerRole && $viewerRole->hasPermission(Permissions::CAN_MANAGE_STRUCTURES),
'can_manage_diplomacy' => $viewerRole && $viewerRole->hasPermission(Permissions::CAN_MANAGE_DIPLOMACY),
'can_declare_war' => $viewerRole && $viewerRole->hasPermission(Permissions::CAN_DECLARE_WAR),
];

// 3. Format Alliance Data
$formattedAlliance = [
'id' => $alliance->id,
'name' => $alliance->name,
'tag' => $alliance->tag,
'description' => $alliance->description,
'profile_picture_url' => $alliance->profile_picture_url,
'bank_credits' => number_format($alliance->bank_credits),
'is_joinable' => $alliance->is_joinable,
'recruitment_status_text' => $alliance->is_joinable ? 'Open' : 'Application Only',
'recruitment_status_color' => $alliance->is_joinable ? 'var(--accent-green)' : 'var(--accent-blue)',
];

// 4. Process Member Roster (Add flags for "Kick/Promote" buttons per row)
$formattedMembers = [];
foreach ($data['members'] as $member) {
$isSelf = ($viewer->id === $member['id']);
$isMemberLeader = ($member['alliance_role_name'] === 'Leader');

// Logic: Can the viewer manage THIS specific member?
// Must have permission, target must not be self, target must not be leader.
$canManageThisMember = ($permissions['can_kick'] || $permissions['can_manage_roles'])
&& !$isSelf
&& !$isMemberLeader;

            $formattedMembers[] = [
                'id' => $member['id'],
                'character_name' => $member['character_name'],
                'role_name' => $member['alliance_role_name'] ?? 'None',
                'role_id' => $member['alliance_role_id'],
                'profile_picture_url' => $member['profile_picture_url'] ?? null,
                'can_be_managed' => $canManageThisMember
            ];
        }
// 5. Process Bank Logs (Format Dates)
$formattedLogs = [];
foreach ($data['bankLogs'] as $log) {
$formattedLogs[] = [
'message' => $log->message,
'amount' => $log->amount,
'formatted_amount' => ($log->amount >= 0 ? '+' : '') . number_format($log->amount),
'css_class' => $log->amount >= 0 ? 'positive' : 'negative',
'date' => (new DateTime($log->created_at))->format('M d - H:i')
];
}

// 6. Process Loans (Format Dates and Amounts)
$formatLoan = function($loan) use ($viewer) {
return [
'id' => $loan->id,
'user_id' => $loan->user_id,
'character_name' => $loan->character_name,
'amount_requested' => number_format($loan->amount_requested),
'amount_to_repay' => number_format($loan->amount_to_repay),
'raw_amount_to_repay' => $loan->amount_to_repay, // For data attributes
'date' => (new DateTime($loan->created_at))->format('M d'),
'is_my_loan' => ($loan->user_id === $viewer->id)
];
};

return [
'layoutMode' => 'full',
'title' => $alliance->name,

// State & Permissions
'state' => [
'is_member' => $isMember,
'is_leader' => $isLeader,
'has_applied' => $hasApplied,
'application_id' => $hasApplied ? $data['userApplication']->id : null,
'viewer_id' => $viewer->id // Needed for JS checks usually
],
'perms' => $permissions,

// Data Objects
'alliance' => $formattedAlliance,
'members' => $formattedMembers,
'roles' => $data['roles'], // Passed through for select dropdowns
'applications' => $data['applications'], // Passed through (Entities are fine for simple loops)

// Financials
'logs' => $formattedLogs,
'loans' => [
'pending' => array_map($formatLoan, $data['pendingLoans']),
'active' => array_map($formatLoan, $data['activeLoans']),
'historical' => array_map($formatLoan, $data['historicalLoans'])
]
];
}
}