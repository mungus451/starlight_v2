<?php
/* @var array $costs */
/* @var array $bounties */
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
        <p style="font-size: 0.85rem; color: var(--muted); flex-grow: 1;">Import 500 untrained citizens immediately.</p>
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
                <input type="text" name="target_name" placeholder="Target Name" required>
            </div>
            <button class="btn-submit btn-reject" style="width: 100%;">
                Execute (<?= number_format($costs['shadow_contract']) ?> 💎)
            </button>
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
                        <label>Target Name</label>
                        <input type="text" name="target_name" required placeholder="Commander Name">
                    </div>
                    <div class="form-group">
                        <label>Amount (Crystals)</label>
                        <input type="number" name="amount" min="10" required placeholder="100">
                    </div>
                    <button type="submit" class="btn-submit btn-reject">Post Contract</button>
                </form>
            </div>
        </div>
    </div>

</div>
</div>