<?php
/* @var array $costs */
/* @var array $bounties */
/* @var array $targets */
/* @var string $csrf_token */
?>
<div class="container-full">
<h1>The Void Syndicate</h1>
<div class="tabs-nav" style="justify-content: center; margin-bottom: 2rem;">
<a href="/black-market/converter" class="tab-link">Crystal Exchange</a>
<a href="/black-market/actions" class="tab-link active">Undermarket</a>
</div>
<div class="item-grid">
<!-- Loot Box -->
<div class="item-card" style="border-color: var(--accent-2); box-shadow: 0 0 20px rgba(249, 199, 79, 0.15);">
    <div class="card-header">
        <h3 style="color: var(--accent-2);">Void Container</h3>
    </div>
    <div class="card-body" style="text-align: center;">
        <p style="color: var(--muted); font-size: 0.9rem;">A sealed container from deep space. Could contain credits, advanced units, or nothing but dust.</p>
        <div style="margin: 1.5rem 0; font-size: 3rem;">📦</div>
        <form action="/black-market/buy/lootbox" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <button class="btn-submit btn-accent" style="width: 100%;">
                Open (<?= number_format($costs['void_container']) ?> 💎)
            </button>
        </form>
    </div>
</div>

<!-- Utility Items -->
<div class="item-card">
    <h4>Neural Rewiring</h4>
    <p style="font-size: 0.85rem; color: var(--muted); flex-grow: 1;">Reset all allocated stat points to the available pool.</p>
    <form action="/black-market/buy/respec" method="POST" style="margin-top: 1rem;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <button class="btn-submit" style="width: 100%;">Purchase (<?= number_format($costs['stat_respec']) ?> 💎)</button>
    </form>
</div>

<div class="item-card">
    <h4>Stim-Pack Injection</h4>
    <p style="font-size: 0.85rem; color: var(--muted); flex-grow: 1;">Instantly restore 50 attack turns.</p>
    <form action="/black-market/buy/refill" method="POST" style="margin-top: 1rem;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <button class="btn-submit" style="width: 100%;">Refill (<?= number_format($costs['turn_refill']) ?> 💎)</button>
    </form>
</div>

<div class="item-card">
    <h4>Smuggled Citizens</h4>
    <p style="font-size: 0.85rem; color: var(--muted); flex-grow: 1;">Import 50,000 untrained citizens immediately.</p>
    <form action="/black-market/buy/citizens" method="POST" style="margin-top: 1rem;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <button class="btn-submit" style="width: 100%;">Import (<?= number_format($costs['citizen_package']) ?> 💎)</button>
    </form>
</div>

<!-- Shadow Contract (Option 4) -->
<div class="item-card">
    <h4>Shadow Contract</h4>
    <p style="font-size: 0.85rem; color: var(--muted); flex-grow: 1;">
        Launch an anonymous attack via The Void Syndicate. 
        Victim sees "The Void Syndicate" in logs.
    </p>
    <form action="/black-market/shadow" method="POST" style="margin-top: 1rem;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <div class="form-group" style="margin-bottom: 0.5rem;">
            <select name="target_name" required>
                <option value="">Select Target...</option>
                <?php foreach ($targets as $target): ?>
                    <option value="<?= htmlspecialchars($target['character_name']) ?>">
                        <?= htmlspecialchars($target['character_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn-submit btn-reject" style="width: 100%;">
            Execute (<?= number_format($costs['shadow_contract']) ?> 💎)
        </button>
    </form>
</div>

<!-- NEW: Electronic Warfare -->
<div class="item-card">
    <h4><i class="fas fa-satellite-dish"></i> Radar Jamming</h4>
    <p style="font-size: 0.85rem; color: var(--muted); flex-grow: 1;">
        Scramble incoming sensors. All spy attempts against you will fail for 4 hours.
    </p>
    <form action="/black-market/buy/radar_jamming" method="POST" style="margin-top: 1rem;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <button class="btn-submit btn-accent" style="width: 100%;">
            Jam Signals (<?= number_format($costs['radar_jamming'] ?? 50000) ?> 💎)
        </button>
    </form>
</div>

<!-- NEW: Safehouse -->
<div class="item-card">
    <h4><i class="fas fa-house-user"></i> Safehouse Access</h4>
    <p style="font-size: 0.85rem; color: var(--muted); flex-grow: 1;">
        Disappear from the grid. You are immune to attacks for 6 hours. 
        <span class="text-danger">Attacking or spying breaks this shield instantly.</span>
    </p>
    
    <?php if (!empty($isSafehouseCooldown)): ?>
        <div class="alert alert-warning text-center p-2 mb-2" style="font-size: 0.8rem;">
            <i class="fas fa-lock"></i> Systems Rebooting (1h Cooldown)
        </div>
        <button class="btn-submit" style="width: 100%; background: #2d3748; color: #718096; cursor: not-allowed;" disabled>
            Locked
        </button>
    <?php else: ?>
        <form action="/black-market/buy/safehouse" method="POST" style="margin-top: 1rem;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <button class="btn-submit" style="width: 100%; background: #4a5568;">
                Rent (<?= number_format($costs['safehouse'] ?? 100000) ?> 💎)
            </button>
        </form>
    <?php endif; ?>
</div>

<!-- NEW: High Risk Protocol -->
<div class="item-card">
    <h4><i class="fas fa-skull"></i> High Risk Protocol</h4>
    <p style="font-size: 0.85rem; color: var(--muted); flex-grow: 1;">
        +50% Income, -10% Attacker Casualties. Disables Safehouse. Duration: 24h.
    </p>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
        <span class="badge" style="background: var(--accent-red); color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem;">Aggressive</span>
        <span class="text-accent"><?= number_format($costs['high_risk_buff'] ?? 50000000) ?> 💎</span>
    </div>

    <?php if (!empty($isHighRiskActive)): ?>
        <div class="alert alert-danger text-center p-2 mb-2" style="font-size: 0.8rem; border: 1px solid var(--accent-red); background: rgba(255, 0, 0, 0.1);">
            <i class="fas fa-radiation"></i> Protocol Active
        </div>
        <form action="/black-market/buy/terminate_high_risk" method="POST" style="margin-top: 1rem;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <button class="btn-submit btn-reject" style="width: 100%; border: 1px solid var(--accent-red);">
                <i class="fas fa-stop-circle"></i> Terminate Early
            </button>
        </form>
    <?php else: ?>
        <form action="/black-market/buy/high_risk" method="POST" style="margin-top: 1rem;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <button class="btn-submit btn-reject" style="width: 100%;">
                Initiate Protocol
            </button>
        </form>
    <?php endif; ?>
</div>

<!-- NEW: Laundering -->
<div class="item-card">
    <h4><i class="fas fa-money-bill-wave"></i> Resource Laundering</h4>
    <p style="font-size: 0.85rem; color: var(--muted); flex-grow: 1;">
        Convert Credits into Untraceable Chips. Chips cannot be stolen in battle.
        <br><strong class="text-accent">Rate: 1.15 Credits -> 1 Chip</strong>
    </p>
    <form action="/black-market/launder" method="POST" style="margin-top: 1rem;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <div class="form-group">
            <input type="hidden" name="amount" id="launder-amount-hidden" value="0">
            <input type="text" id="launder-amount-display" placeholder="Credits to Launder" required min="100">
        </div>
        <button class="btn-submit" style="width: 100%;">Launder</button>
    </form>
</div>

<!-- Bounty Board -->
<div class="item-card grid-col-span-2">
    <div class="card-header">
        <h3>Bounty Board</h3>
    </div>
    
    <div class="split-grid">
        <!-- List -->
        <div>
            <h5 style="color: var(--accent-red); border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">Active Contracts</h5>
            <ul class="data-list" style="max-height: 300px; overflow-y: auto;">
                <?php if (empty($bounties)): ?>
                    <li class="data-item" style="color: var(--muted); justify-content: center;">No active bounties.</li>
                <?php else: ?>
                    <?php foreach ($bounties as $b): ?>
                        <li class="data-item" style="justify-content: space-between;">
                            <div style="display: flex; gap: 1rem; align-items: center;">
                                <i class="fas fa-crosshairs text-danger"></i>
                                <strong><?= htmlspecialchars($b['target_name']) ?></strong>
                            </div>
                            <strong class="text-accent"><?= number_format($b['amount']) ?> 💎</strong>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Form -->
        <div style="background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 12px; border: 1px solid var(--border);">
            <h5 style="margin-top: 0; color: #fff;">Place a Bounty</h5>
            <form action="/black-market/bounty/place" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <div class="form-group">
                    <label>Target</label>
                    <select name="target_name" required>
                        <option value="">Select Target...</option>
                        <?php foreach ($targets as $target): ?>
                            <option value="<?= htmlspecialchars($target['character_name']) ?>">
                                <?= htmlspecialchars($target['character_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount (Crystals)</label>
                    <input type="hidden" name="amount" id="bounty-amount-hidden" value="0">
                    <input type="text" id="bounty-amount-display" min="10" required placeholder="100">
                </div>
                <button type="submit" class="btn-submit btn-reject">Post Contract</button>
            </form>
        </div>
    </div>
</div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const launderDisplay = document.getElementById('launder-amount-display');
        const launderHidden = document.getElementById('launder-amount-hidden');
        if (launderDisplay && launderHidden) {
            StarlightUtils.setupInputMask(launderDisplay, launderHidden);
        }

        const bountyDisplay = document.getElementById('bounty-amount-display');
        const bountyHidden = document.getElementById('bounty-amount-hidden');
        if (bountyDisplay && bountyHidden) {
            StarlightUtils.setupInputMask(bountyDisplay, bountyHidden);
        }
    });
</script>