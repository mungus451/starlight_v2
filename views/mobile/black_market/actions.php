<?php
// --- Mobile Undermarket Actions View ---
/* @var array $costs */
/* @var array $bounties */
/* @var array $targets */
/* @var string $csrf_token */
?>
<div class="mobile-content">
    <div class="player-hub" style="margin-bottom: 1rem; padding: 1rem; background: transparent; border: none; box-shadow: none;">
        <h1 style="font-family: 'Orbitron', sans-serif; font-size: 2rem; color: var(--mobile-text-primary); text-shadow: 0 0 10px var(--mobile-text-primary);">The Undermarket</h1>
        <p style="color: var(--mobile-text-secondary); font-size: 0.9rem; margin: 0.5rem 1rem 0 1rem; text-align: center;"> illicit services and black market dealings.</p>
    </div>

    <!-- WRAPPER FOR TABS LOGIC -->
    <div class="nested-tabs-container">
        
        <!-- Tab Navigation -->
        <div class="mobile-tabs nested-tabs">
            <a href="#" class="tab-link active" data-tab-target="tab-services">Services</a>
            <a href="#" class="tab-link" data-tab-target="tab-finance">Finance</a>
            <a href="#" class="tab-link" data-tab-target="tab-ops">Operations</a>
        </div>

        <!-- Tab Content -->
        <div id="tab-content">
            
            <!-- Services Tab -->
            <div id="tab-services" class="nested-tab-content active">
                <!-- Turn Refill -->
                <div class="mobile-card">
                    <div class="mobile-card-header"><h3><i class="fas fa-bolt"></i> Energy Refill</h3></div>
                    <div class="mobile-card-content" style="display: block;">
                        <p class="structure-description">Restores full energy turns immediately.</p>
                        <form action="/black-market/buy/refill" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <button type="submit" class="btn" style="width: 100%;">
                                Buy (<?= number_format($costs['turn_refill']) ?> 💎)
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Citizens -->
                <div class="mobile-card">
                    <div class="mobile-card-header"><h3><i class="fas fa-users"></i> Import Citizens</h3></div>
                    <div class="mobile-card-content" style="display: block;">
                        <p class="structure-description">Adds 50,000 citizens to your population.</p>
                        <form action="/black-market/buy/citizens" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <button type="submit" class="btn" style="width: 100%;">
                                Buy (<?= number_format($costs['citizen_package']) ?> 💎)
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Respec -->
                <div class="mobile-card">
                    <div class="mobile-card-header"><h3><i class="fas fa-brain"></i> Neural Respec</h3></div>
                    <div class="mobile-card-content" style="display: block;">
                        <p class="structure-description">Reset all skill points. <span class="text-danger">WARNING: Clears current allocation.</span></p>
                        <form action="/black-market/buy/respec" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <button type="submit" class="btn btn-warning" style="width: 100%;">
                                Reset (<?= number_format($costs['stat_respec']) ?> 💎)
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Void Container -->
                <div class="mobile-card">
                    <div class="mobile-card-header"><h3><i class="fas fa-box-open"></i> Void Container</h3></div>
                    <div class="mobile-card-content" style="display: block;">
                        <p class="structure-description">Contains random resources or rare items. High risk.</p>
                        <form action="/black-market/buy/lootbox" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <button type="submit" class="btn" style="width: 100%;">
                                Open (<?= number_format($costs['void_container']) ?> 💎)
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Finance Tab -->
            <div id="tab-finance" class="nested-tab-content">
                <!-- Launder -->
                <div class="mobile-card">
                    <div class="mobile-card-header"><h3><i class="fas fa-soap"></i> Launder Credits</h3></div>
                    <div class="mobile-card-content" style="display: block;">
                        <p class="structure-description">Clean your dirty credits. Fee: 25%.</p>
                        <form action="/black-market/launder" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <div class="form-group">
                                <input type="number" name="amount" class="mobile-input" placeholder="Amount to Launder" min="100" style="width: 100%;">
                            </div>
                            <button type="submit" class="btn" style="width: 100%;">Launder</button>
                        </form>
                    </div>
                </div>

                <!-- Withdraw Chips -->
                <div class="mobile-card">
                    <div class="mobile-card-header"><h3><i class="fas fa-memory"></i> Withdraw Chips</h3></div>
                    <div class="mobile-card-content" style="display: block;">
                        <p class="structure-description">Convert Untraceable Chips back to Credits.</p>
                        <form action="/black-market/withdraw-chips" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <div class="form-group">
                                <input type="number" name="amount" class="mobile-input" placeholder="Chips Amount" min="1" style="width: 100%;">
                            </div>
                            <button type="submit" class="btn" style="width: 100%;">Withdraw</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Operations Tab -->
            <div id="tab-ops" class="nested-tab-content">
                <!-- High Risk Protocol -->
                <div class="mobile-card">
                    <div class="mobile-card-header"><h3><i class="fas fa-skull"></i> High Risk Protocol</h3></div>
                    <div class="mobile-card-content" style="display: block;">
                        <p class="structure-description">+50% Income, -10% Attacker Casualties. Disables Safehouse. Duration: 24h.</p>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span style="background: var(--mobile-accent-red); color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem;">Aggressive</span>
                            <span style="color: var(--mobile-text-secondary);"><?= number_format($costs['high_risk_buff'] ?? 50000000) ?> 💎</span>
                        </div>

                        <?php if (!empty($isHighRiskActive)): ?>
                            <div style="text-align: center; padding: 0.5rem; margin-bottom: 0.5rem; border: 1px solid var(--mobile-accent-red); background: rgba(255, 0, 0, 0.1); color: var(--mobile-accent-red); border-radius: 4px;">
                                <i class="fas fa-radiation"></i> Protocol Active
                            </div>
                            <form action="/black-market/buy/terminate_high_risk" method="POST" style="margin-top: 1rem;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <button type="submit" class="btn btn-danger" style="width: 100%;">
                                    <i class="fas fa-stop-circle"></i> Terminate Early
                                </button>
                            </form>
                        <?php else: ?>
                            <form action="/black-market/buy/high_risk" method="POST" style="margin-top: 1rem;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <button type="submit" class="btn btn-danger" style="width: 100%;">
                                    Initiate Protocol
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Place Bounty -->
                <div class="mobile-card">
                    <div class="mobile-card-header"><h3><i class="fas fa-crosshairs"></i> Place Bounty</h3></div>
                    <div class="mobile-card-content" style="display: block;">
                        <form action="/black-market/bounty/place" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <div class="form-group">
                                <input type="text" name="target_name" class="mobile-input" placeholder="Target Name" required style="width: 100%; margin-bottom: 0.5rem;">
                                <input type="number" name="amount" class="mobile-input" placeholder="Bounty Amount" min="1000" required style="width: 100%;">
                            </div>
                            <button type="submit" class="btn btn-danger" style="width: 100%;">Place Bounty</button>
                        </form>
                    </div>
                </div>

                <!-- Shadow Contract -->
                <div class="mobile-card">
                    <div class="mobile-card-header"><h3><i class="fas fa-user-ninja"></i> Shadow Contract</h3></div>
                    <div class="mobile-card-content" style="display: block;">
                        <p class="structure-description">Anonymous attack. No logs generated.</p>
                        <form action="/black-market/shadow" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <div class="form-group">
                                <input type="text" name="target_name" class="mobile-input" placeholder="Target Name" required style="width: 100%;">
                            </div>
                            <button type="submit" class="btn btn-danger" style="width: 100%;">
                                Execute (<?= number_format($costs['shadow_contract']) ?> 💎)
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Radar Jamming -->
                <div class="mobile-card">
                    <div class="mobile-card-header"><h3><i class="fas fa-broadcast-tower"></i> Radar Jamming</h3></div>
                    <div class="mobile-card-content" style="display: block;">
                        <p class="structure-description">Prevents others from spying on you for 24h.</p>
                        <form action="/black-market/buy/radar_jamming" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <button type="submit" class="btn" style="width: 100%;">
                                Activate (<?= number_format($costs['radar_jamming']) ?> 💎)
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Safehouse -->
                <div class="mobile-card">
                    <div class="mobile-card-header"><h3><i class="fas fa-home"></i> Safehouse</h3></div>
                    <div class="mobile-card-content" style="display: block;">
                        <p class="structure-description">Prevents attacks for 12h. Cannot attack while active.</p>
                        <form action="/black-market/buy/safehouse" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <button type="submit" class="btn" style="width: 100%;">
                                Secure (<?= number_format($costs['safehouse']) ?> 💎)
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div> <!-- End nested-tabs-container -->
    
    <div style="margin-top: 2rem; text-align: center;">
        <a href="/black-market/converter" class="btn btn-outline" style="width: 100%;">
            <i class="fas fa-exchange-alt"></i> Go to Exchange
        </a>
    </div>
</div>
