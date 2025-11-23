<?php
// --- Helper variables from the controller ---
/* @var \App\Models\Entities\Treaty[] $treaties */
/* @var \App\Models\Entities\Rivalry[] $rivalries */
/* @var \App\Models\Entities\Alliance[] $otherAlliances */
/* @var \App\Models\Entities\User $viewer */
/* @var bool $canManage */
/* @var int $allianceId */

// --- Filter treaties for display ---
$proposed_to_us = [];
$proposed_by_us = [];
$active = [];
$historical = [];

foreach ($treaties as $treaty) {
    if ($treaty->status === 'proposed') {
        if ($treaty->alliance2_id === $allianceId) {
            $proposed_to_us[] = $treaty;
        } else {
            $proposed_by_us[] = $treaty;
        }
    } elseif ($treaty->status === 'active') {
        $active[] = $treaty;
    } else {
        $historical[] = $treaty;
    }
}
?>

<div class="container-full">
    <h1>Alliance Diplomacy</h1>
    
    <a href="/alliance/profile/<?= $allianceId ?>" class="btn-submit btn-accent" style="max-width: 400px; margin: 0 auto 1.5rem auto;">
        &laquo; Back to Alliance Profile
    </a>

    <div class="item-grid">
        <?php if ($canManage): ?>
            <div class="item-card">
                <h4>Propose Treaty</h4>
                <form action="/alliance/diplomacy/treaty/propose" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    <div class="form-group">
                        <label for="target_alliance_id">Target Alliance</label>
                        <select name="target_alliance_id" id="target_alliance_id" required>
                            <option value="">Select an alliance...</option>
                            <?php foreach ($otherAlliances as $a): ?>
                                <option value="<?= $a->id ?>">[<?= htmlspecialchars($a->tag) ?>] <?= htmlspecialchars($a->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="treaty_type">Treaty Type</label>
                        <select name="treaty_type" id="treaty_type" required>
                            <option value="peace">Peace</option>
                            <option value="non_aggression">Non-Aggression Pact</option>
                            <option value="mutual_defense">Mutual Defense</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="terms">Terms (Optional)</label>
                        <textarea name="terms" id="terms" placeholder="e.g., Alliance A pays 10M credits to Alliance B..." style="min-height: 80px;"></textarea>
                    </div>
                    <button type="submit" class="btn-submit">Propose Treaty</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="item-card">
            <h4>Declare Rivalry</h4>
            <p class="form-note">Declaring a rivalry increases the 'Heat' level between alliances, signaling hostility.</p>
            <form action="/alliance/diplomacy/rivalry/declare" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                <div class="form-group">
                    <label for="target_rival_id">Target Alliance</label>
                    <select name="target_alliance_id" id="target_rival_id" required>
                        <option value="">Select an alliance...</option>
                        <?php foreach ($otherAlliances as $a): ?>
                            <option value="<?= $a->id ?>">[<?= htmlspecialchars($a->tag) ?>] <?= htmlspecialchars($a->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-submit btn-reject">Declare Rivalry</button>
            </form>
        </div>

        <div class="item-card grid-col-span-2">
            <h4>Diplomatic Relations</h4>
            
            <h5 class="text-status-proposed" style="margin: 0.5rem 0;">Pending Proposals (<?= count($proposed_to_us) ?>)</h5>
            <ul class="data-list">
                <?php if (empty($proposed_to_us)): ?>
                    <li class="data-item" style="color: var(--muted); justify-content: center;">No pending proposals received.</li>
                <?php endif; ?>
                <?php foreach ($proposed_to_us as $treaty): ?>
                    <li class="data-item">
                        <div class="item-info">
                            <span class="role"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $treaty->treaty_type))) ?></span>
                            <span class="name">Proposed by <?= htmlspecialchars($treaty->alliance1_name) ?></span>
                        </div>
                        <?php if ($canManage): ?>
                            <div class="item-actions">
                                <form action="/alliance/diplomacy/treaty/accept/<?= $treaty->id ?>" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                    <button type="submit" class="btn-submit btn-accept">Accept</button>
                                </form>
                                <form action="/alliance/diplomacy/treaty/decline/<?= $treaty->id ?>" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                    <button type="submit" class="btn-submit btn-reject">Decline</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <h5 class="text-status-active" style="margin: 1.5rem 0 0.5rem 0;">Active Treaties (<?= count($active) ?>)</h5>
            <ul class="data-list">
                <?php if (empty($active)): ?>
                    <li class="data-item" style="color: var(--muted); justify-content: center;">No active treaties.</li>
                <?php endif; ?>
                <?php foreach ($active as $treaty): ?>
                    <li class="data-item">
                        <div class="item-info">
                            <span class="role"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $treaty->treaty_type))) ?></span>
                            <span class="name">With <?= htmlspecialchars($treaty->alliance1_id === $allianceId ? $treaty->alliance2_name : $treaty->alliance1_name) ?></span>
                        </div>
                        <?php if ($canManage): ?>
                            <div class="item-actions">
                                <form action="/alliance/diplomacy/treaty/break/<?= $treaty->id ?>" method="POST" onsubmit="return confirm('Are you sure you want to break this treaty?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                    <button type="submit" class="btn-submit btn-reject">Break Treaty</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="item-card grid-col-span-2">
            <h4>Rivalries</h4>
            <ul class="data-list">
                <?php if (empty($rivalries)): ?>
                    <li class="data-item" style="color: var(--muted); justify-content: center;">No rivalries declared.</li>
                <?php endif; ?>
                <?php foreach ($rivalries as $rivalry): ?>
                    <?php 
                    $rivalName = $rivalry->alliance_a_id === $allianceId ? $rivalry->alliance_b_name : $rivalry->alliance_a_name;
                    ?>
                    <li class="data-item">
                        <span class="name">Rivalry with <strong><?= htmlspecialchars($rivalName) ?></strong></span>
                        <span class="role text-status-broken">Heat Level: <?= $rivalry->heat_level ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>