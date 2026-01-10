<?php
// --- Helper variables from AllianceProfilePresenter ---
/* @var array $alliance (Formatted Data) */
/* @var array $state (User State Flags) */
/* @var array $perms (User Permission Flags) */
/* @var array $members (Formatted Roster) */
/* @var array $logs (Formatted Bank Logs) */
/* @var array $loans (Categorized Loans) */
/* @var \App\Models\Entities\AllianceRole[] $roles */
/* @var \App\Models\Entities\AllianceApplication[] $applications */
/* @var string $csrf_token */

// Calculate Member Count from $members array since it's not in $alliance sometimes
$memberCount = count($members);
$capacity = 20; // Default or fetch from config if available, hardcoding 20 for now as standard
?>

<style>
    /* --- Alliance Command Center Specific Styles --- */
    .alliance-hero {
        position: relative;
        background: linear-gradient(180deg, rgba(13, 17, 23, 0) 0%, rgba(13, 17, 23, 0.9) 100%), 
                    url('/img/backgrounds/alliance-hero-bg.jpg'); /* Fallback or specific bg */
        background-size: cover;
        background-position: center;
        border-radius: 12px;
        border: 1px solid var(--border);
        padding: 2rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 2rem;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    
    /* Animated Grid Overlay */
    .alliance-hero::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background-image: 
            linear-gradient(rgba(45, 209, 209, 0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(45, 209, 209, 0.03) 1px, transparent 1px);
        background-size: 30px 30px;
        z-index: 0;
        pointer-events: none;
    }

    .hero-avatar-container {
        position: relative;
        z-index: 1;
        width: 140px;
        height: 140px;
        flex-shrink: 0;
    }

    .hero-avatar {
        width: 100%;
        height: 100%;
        border-radius: 12px;
        object-fit: cover;
        border: 2px solid var(--accent);
        box-shadow: 0 0 20px rgba(45, 209, 209, 0.3);
        background: #000;
    }

    .hero-content {
        position: relative;
        z-index: 1;
        flex-grow: 1;
    }

    .hero-title {
        font-family: 'Orbitron', sans-serif;
        font-size: 2.5rem;
        margin: 0;
        color: #fff;
        text-shadow: 0 0 10px rgba(255,255,255,0.3);
        line-height: 1.1;
    }

    .hero-tag {
        color: var(--accent-2);
        font-weight: 700;
    }

    .hero-stats {
        display: flex;
        gap: 1.5rem;
        margin-top: 1rem;
        font-size: 0.9rem;
        color: var(--muted);
    }

    .stat-pill {
        background: rgba(255,255,255,0.05);
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        border: 1px solid rgba(255,255,255,0.1);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .stat-pill i { color: var(--accent); }

    .hero-actions {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.75rem;
    }

    /* --- Quick Ops Bar --- */
    .quick-ops-bar {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .quick-op-btn {
        background: rgba(16, 20, 30, 0.6);
        border: 1px solid var(--border);
        padding: 1rem;
        border-radius: 8px;
        text-align: center;
        color: var(--muted);
        transition: all 0.2s ease;
        text-decoration: none;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }

    .quick-op-btn:hover {
        background: rgba(45, 209, 209, 0.1);
        border-color: var(--accent);
        color: #fff;
        transform: translateY(-2px);
    }

    .quick-op-btn i {
        font-size: 1.5rem;
        margin-bottom: 0.25rem;
    }
    
    .quick-op-btn.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }

    /* --- Tab Content Areas --- */
    .tab-content { display: none; animation: fadeIn 0.3s ease; }
    .tab-content.active { display: block; }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Custom Widget Styles */
    .treasury-widget {
        background: radial-gradient(circle at top right, rgba(249, 199, 79, 0.1), rgba(13, 17, 23, 0.8));
        border: 1px solid rgba(249, 199, 79, 0.3);
    }
    
    .treasury-balance {
        font-family: 'Orbitron', sans-serif;
        font-size: 2rem;
        color: var(--accent-2);
        margin: 0.5rem 0;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .alliance-hero {
            flex-direction: column;
            text-align: center;
            gap: 1rem;
        }
        .hero-stats {
            justify-content: center;
            flex-wrap: wrap;
        }
        .hero-actions {
            align-items: center;
            width: 100%;
        }
        .hero-actions .btn-submit {
            width: 100%;
        }
    }

    /* --- Widget Headers (Prominent) --- */
    .dashboard-card h4 {
        text-align: center;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-family: 'Orbitron', sans-serif;
        font-weight: 700;
        margin-bottom: 1.5rem;
        padding-bottom: 10px;
        border-bottom: 2px solid rgba(255,255,255,0.05);
        position: relative;
    }

    .dashboard-card h4::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 2px;
        background: var(--accent);
        box-shadow: 0 0 10px var(--accent);
    }

    /* --- Charter Terminal --- */
    .charter-terminal {
        background: #020305;
        border: 1px solid rgba(45, 209, 209, 0.3);
        box-shadow: 0 0 15px rgba(45, 209, 209, 0.05);
        border-radius: 4px;
        font-family: 'Courier New', Courier, monospace;
        position: relative;
        overflow: hidden;
    }
    
    .charter-header {
        background: rgba(45, 209, 209, 0.1);
        border-bottom: 1px solid rgba(45, 209, 209, 0.2);
        padding: 8px 12px;
        font-size: 0.7rem;
        color: var(--accent);
        display: flex;
        justify-content: space-between;
        letter-spacing: 1px;
    }
    
    .charter-body {
        padding: 20px;
        color: #a8afd4;
        font-size: 0.95rem;
        line-height: 1.6;
        white-space: pre-wrap;
        position: relative;
        z-index: 1;
    }
    
    .charter-body::after {
        content: '_';
        animation: blink 1s infinite;
    }
    
    @keyframes blink { 50% { opacity: 0; } }

    .charter-terminal::before {
        content: "";
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.25) 50%), linear-gradient(90deg, rgba(255, 0, 0, 0.06), rgba(0, 255, 0, 0.02), rgba(0, 0, 255, 0.06));
        background-size: 100% 2px, 3px 100%;
        pointer-events: none;
        z-index: 2;
    }

    /* --- Intel Stream (Futuristic Ledger) --- */
    .intel-stream {
        display: flex;
        flex-direction: column;
        gap: 8px;
        position: relative;
    }
    
    /* Vertical connecting line */
    .intel-stream::before {
        content: '';
        position: absolute;
        left: 24px;
        top: 10px;
        bottom: 10px;
        width: 2px;
        background: rgba(255,255,255,0.05);
        z-index: 0;
    }

    .intel-log {
        display: flex;
        align-items: center;
        background: rgba(13, 17, 23, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-left: 3px solid transparent; /* Status Color */
        border-radius: 4px;
        padding: 10px 15px;
        position: relative;
        transition: all 0.2s ease;
        z-index: 1;
    }
    
    .intel-log:hover {
        background: rgba(45, 209, 209, 0.05);
        transform: translateX(4px);
        box-shadow: -5px 0 15px rgba(0,0,0,0.3);
    }

    /* Status Colors */
    .intel-log.type-money { border-left-color: var(--accent-2); }
    .intel-log.type-alert { border-left-color: var(--bridge-danger); }
    .intel-log.type-info { border-left-color: var(--accent); }

    /* Icon Container */
    .intel-icon {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #111;
        border: 1px solid rgba(255,255,255,0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        flex-shrink: 0;
        box-shadow: 0 0 10px rgba(0,0,0,0.5);
    }
    
    .intel-log.type-money .intel-icon { color: var(--accent-2); border-color: rgba(249, 199, 79, 0.3); }
    .intel-log.type-alert .intel-icon { color: var(--bridge-danger); border-color: rgba(255, 42, 42, 0.3); }
    .intel-log.type-info .intel-icon { color: var(--accent); border-color: rgba(45, 209, 209, 0.3); }

    .intel-content {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    
    .intel-meta {
        font-family: 'Orbitron', sans-serif;
        font-size: 0.7rem;
        color: var(--muted);
        margin-bottom: 2px;
        letter-spacing: 1px;
    }

    .intel-msg {
        font-size: 0.9rem;
        color: #e0e0e0;
    }
    
    .intel-amount {
        font-family: 'Orbitron', sans-serif;
        font-weight: 700;
        margin-left: auto;
        padding-left: 15px;
        white-space: nowrap;
    }
</style>

<div class="container-full">
    
    <!-- ======================= HERO SECTION ======================= -->
    <div class="alliance-hero">
        <div class="hero-avatar-container">
            <?php if ($alliance['profile_picture_url']): ?>
                <img src="/serve/alliance_avatar/<?= htmlspecialchars($alliance['profile_picture_url']) ?>" alt="Logo" class="hero-avatar">
            <?php else: ?>
                <div class="hero-avatar" style="display: flex; align-items: center; justify-content: center; background: #0b0d12;">
                    <i class="fas fa-users fa-3x" style="color: var(--border);"></i>
                </div>
            <?php endif; ?>
        </div>

        <div class="hero-content">
            <h1 class="hero-title">
                <?= htmlspecialchars($alliance['name']) ?> 
                <span class="hero-tag">[<?= htmlspecialchars($alliance['tag']) ?>]</span>
            </h1>
            
            <div class="hero-stats">
                <div class="stat-pill" title="Net Worth">
                    <i class="fas fa-chart-line"></i> 
                    <?= number_format($alliance['net_worth'] ?? 0) ?>
                </div>
                <div class="stat-pill" title="Members">
                    <i class="fas fa-users"></i> 
                    <?= $memberCount ?> / <?= $capacity ?>
                </div>
                <div class="stat-pill" title="Recruitment Status">
                    <?php if ($alliance['is_joinable']): ?>
                        <i class="fas fa-door-open text-success"></i> Open
                    <?php else: ?>
                        <i class="fas fa-file-signature text-warning"></i> Application
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="hero-actions">
            <?php if ($state['is_member']): ?>
                <?php if (!$state['is_leader']): ?>
                    <form action="/alliance/leave" method="POST" onsubmit="return confirm('WARNING: Are you sure you want to leave this alliance?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                        <button type="submit" class="btn-submit btn-reject btn-sm">
                            <i class="fas fa-sign-out-alt"></i> Leave Alliance
                        </button>
                    </form>
                <?php else: ?>
                    <span class="badge bg-warning text-dark">COMMANDER</span>
                <?php endif; ?>
            <?php elseif ($state['has_applied']): ?>
                <div class="text-warning mb-2"><i class="fas fa-clock"></i> Application Pending</div>
                <form action="/alliance/cancel-app/<?= $state['application_id'] ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    <button type="submit" class="btn-submit btn-reject btn-sm">Cancel Application</button>
                </form>
            <?php elseif ($state['viewer_id']): ?>
                <form action="/alliance/apply/<?= $alliance['id'] ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    <button type="submit" class="btn-submit <?= $alliance['is_joinable'] ? 'btn-accept' : '' ?> btn-lg">
                        <?= $alliance['is_joinable'] ? 'Join Now' : 'Apply for Membership' ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- ======================= QUICK OPS BAR ======================= -->
    <?php if ($state['is_member']): ?>
        <div class="quick-ops-bar">
            <a href="/alliance/forum" class="quick-op-btn">
                <i class="fas fa-comments text-neon-blue"></i>
                <span>Comms Array</span>
            </a>
            
            <?php if ($perms['can_manage_structures']): ?>
                <a href="/alliance/structures" class="quick-op-btn">
                    <i class="fas fa-building text-warning"></i>
                    <span>Infrastructure</span>
                </a>
            <?php else: ?>
                <div class="quick-op-btn disabled">
                    <i class="fas fa-building"></i>
                    <span>Infrastructure</span>
                </div>
            <?php endif; ?>

            <?php if ($perms['can_manage_diplomacy']): ?>
                <a href="/alliance/diplomacy" class="quick-op-btn">
                    <i class="fas fa-handshake text-success"></i>
                    <span>Diplomacy</span>
                </a>
            <?php else: ?>
                <div class="quick-op-btn disabled">
                    <i class="fas fa-handshake"></i>
                    <span>Diplomacy</span>
                </div>
            <?php endif; ?>

            <?php if ($perms['can_declare_war']): ?>
                <a href="/alliance/war" class="quick-op-btn">
                    <i class="fas fa-skull text-danger"></i>
                    <span>War Room</span>
                </a>
            <?php else: ?>
                <div class="quick-op-btn disabled">
                    <i class="fas fa-skull"></i>
                    <span>War Room</span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ======================= MAIN CONTENT TABS ======================= -->
    
    <!-- Tab Navigation -->
    <div class="tabs-nav mb-4 justify-content-center">
        <a class="tab-link active" data-tab="tab-ops"><i class="fas fa-home me-2"></i> Operations</a>
        <?php if ($state['is_member']): ?>
            <a class="tab-link" data-tab="tab-finance"><i class="fas fa-coins me-2"></i> Financials</a>
            <a class="tab-link" data-tab="tab-roster"><i class="fas fa-users me-2"></i> Roster</a>
        <?php endif; ?>
        <?php if ($state['is_leader'] || $perms['can_edit_profile']): ?>
            <a class="tab-link" data-tab="tab-settings"><i class="fas fa-cog me-2"></i> Admin</a>
        <?php endif; ?>
    </div>

    <!-- TAB 1: OPERATIONS (Overview) -->
    <div id="tab-ops" class="tab-content active">
        <div class="row">
            <!-- Left: Status & Charter -->
            <div class="col-md-5 mb-4">
                
                <!-- Treasury Widget (Member Only) -->
                <?php if ($state['is_member']): ?>
                    <div class="dashboard-card treasury-widget mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0 text-warning"><i class="fas fa-university"></i> Treasury</h4>
                            <span class="badge bg-dark border border-warning">TAX: 0%</span> <!-- Placeholder -->
                        </div>
                        <div class="treasury-balance">
                            <?= $alliance['bank_credits'] ?> <small style="font-size: 1rem; color: var(--muted);">Cr</small>
                        </div>
                        
                        <form action="/alliance/donate" method="POST" class="mt-3">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                            <div class="amount-input-group">
                                <input type="text" id="donate-amount-display" class="formatted-amount form-control bg-dark text-light border-secondary" placeholder="Amount to Donate" required>
                                <input type="hidden" name="amount" id="donate-amount-hidden" value="0">
                                <button type="button" class="btn btn-outline-warning" id="btn-max-donate">Max</button>
                            </div>
                            <button type="submit" class="btn btn-warning w-100 mt-2 fw-bold">
                                <i class="fas fa-donate me-1"></i> Contribute Funds
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- Charter Terminal -->
                <div class="dashboard-card">
                    <h4><i class="fas fa-file-code text-neon-blue"></i> Alliance Manifesto</h4>
                    <div class="charter-terminal">
                        <div class="charter-header">
                            <span>FILE: CHARTER.TXT</span>
                            <span>STATUS: READ_ONLY</span>
                        </div>
                        <div class="charter-body">
                            <?= !empty($alliance['description']) ? htmlspecialchars($alliance['description']) : '<span class="text-muted fst-italic">> SYSTEM_MSG: No data found.</span>' ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Live Intel Stream -->
            <div class="col-md-7">
                <?php if ($state['is_member']): ?>
                    <div class="dashboard-card" style="height: 100%;">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0"><i class="fas fa-satellite-dish text-success"></i> Live Intel Stream</h4>
                            <span class="badge bg-dark border border-secondary text-muted">SECURE CHANNEL</span>
                        </div>
                        
                        <div class="feed-container" style="max-height: 600px; overflow-y: auto; padding-right: 10px;">
                            <?php if (empty($logs)): ?>
                                <div class="text-center text-muted py-5">
                                    <i class="fas fa-wind fa-2x mb-3"></i><br>
                                    All quiet on the western front.
                                </div>
                            <?php else: ?>
                                <div class="intel-stream">
                                    <?php foreach (array_slice($logs, 0, 20) as $log): ?>
                                        <?php 
                                            // Simple Log Classification
                                            $msg = strtolower($log['message']);
                                            $type = 'type-info';
                                            $icon = 'fa-info';
                                            
                                            if (strpos($msg, 'donated') !== false || strpos($msg, 'repaid') !== false) {
                                                $type = 'type-money';
                                                $icon = 'fa-coins';
                                            } elseif (strpos($msg, 'left') !== false || strpos($msg, 'kicked') !== false) {
                                                $type = 'type-alert';
                                                $icon = 'fa-user-slash';
                                            } elseif (strpos($msg, 'joined') !== false) {
                                                $type = 'type-info';
                                                $icon = 'fa-user-plus';
                                            } elseif (strpos($msg, 'loan') !== false) {
                                                $type = 'type-money';
                                                $icon = 'fa-hand-holding-usd';
                                            }
                                        ?>
                                        <div class="intel-log <?= $type ?>">
                                            <div class="intel-icon">
                                                <i class="fas <?= $icon ?>"></i>
                                            </div>
                                            <div class="intel-content">
                                                <div class="intel-meta">
                                                    <?= date('H:i', strtotime($log['date'])) ?> // SERVER_LOG
                                                </div>
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <div class="intel-msg"><?= htmlspecialchars($log['message']) ?></div>
                                                    <?php if (!empty($log['formatted_amount']) && $log['formatted_amount'] !== '0'): ?>
                                                        <div class="intel-amount <?= $log['css_class'] ?>">
                                                            <?= $log['formatted_amount'] ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Guest View -->
                    <div class="dashboard-card text-center py-5">
                        <i class="fas fa-lock fa-3x text-muted mb-3"></i>
                        <h3>Restricted Access</h3>
                        <p class="text-muted">Join this alliance to view operational data.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- TAB 2: FINANCIALS (Member Only) -->
    <?php if ($state['is_member']): ?>
        <div id="tab-finance" class="tab-content">
            <div class="row">
                <!-- Loan Request -->
                <div class="col-md-4 mb-4">
                    <div class="dashboard-card h-100">
                        <h4><i class="fas fa-hand-holding-usd text-info"></i> Request Loan</h4>
                        <p class="text-muted small">Funds will be deducted from the alliance treasury upon approval.</p>
                        
                        <form action="/alliance/loan/request" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                            <div class="form-group mb-3">
                                <label class="form-label text-muted">Amount Needed</label>
                                <input type="text" id="loan-request-display" class="formatted-amount form-control bg-dark text-light" placeholder="0" required>
                                <input type="hidden" name="amount" id="loan-request-hidden" value="0">
                            </div>
                            <button type="submit" class="btn btn-info w-100">Submit Request</button>
                        </form>

                        <hr class="border-secondary my-4">

                        <!-- My Active Loans -->
                        <h5 class="text-white mb-3">Your Active Loans</h5>
                        <?php 
                        $myLoans = array_filter($loans['active'] ?? [], function($l) { return $l['is_my_loan']; });
                        ?>
                        <?php if (empty($myLoans)): ?>
                            <p class="text-muted small fst-italic">You have no active loans.</p>
                        <?php else: ?>
                            <?php foreach ($myLoans as $loan): ?>
                                <div class="bg-darker p-3 rounded mb-2 border border-secondary">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-white">Owed:</span>
                                        <span class="text-danger fw-bold"><?= $loan['amount_to_repay'] ?></span>
                                    </div>
                                    <form action="/alliance/loan/repay/<?= $loan['id'] ?>" method="POST" class="d-flex gap-2">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                        <div class="input-group input-group-sm">
                                            <input type="text" id="repay-amount-display-<?= $loan['id'] ?>" class="form-control formatted-amount repay-amount-display" placeholder="Repay">
                                            <input type="hidden" name="amount" id="repay-amount-hidden-<?= $loan['id'] ?>" class="repay-amount-hidden" value="0">
                                            <button type="button" class="btn btn-outline-secondary btn-max-repay" data-loan-id="<?= $loan['id'] ?>" data-max-repay="<?= $loan['raw_amount_to_repay'] ?>">Max</button>
                                        </div>
                                        <button type="submit" class="btn btn-success btn-sm">Pay</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Active Loans & Pending Requests -->
                <div class="col-md-8">
                    <!-- Pending Approvals (Officer/Leader) -->
                    <?php if ($perms['can_manage_bank'] && !empty($loans['pending'])): ?>
                        <div class="dashboard-card mb-4 border-warning">
                            <h4 class="text-warning"><i class="fas fa-clock"></i> Pending Loan Approvals</h4>
                            <div class="table-responsive">
                                <table class="table table-dark table-hover mb-0">
                                    <thead><tr><th>Member</th><th>Date</th><th>Amount</th><th>Actions</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($loans['pending'] as $loan): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($loan['character_name']) ?></td>
                                                <td><?= $loan['date'] ?></td>
                                                <td class="text-info"><?= $loan['amount_requested'] ?></td>
                                                <td>
                                                    <form action="/alliance/loan/approve/<?= $loan['id'] ?>" method="POST" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                                        <button class="btn btn-sm btn-success" title="Approve"><i class="fas fa-check"></i></button>
                                                    </form>
                                                    <form action="/alliance/loan/deny/<?= $loan['id'] ?>" method="POST" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                                        <button class="btn btn-sm btn-danger" title="Deny"><i class="fas fa-times"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- All Active Loans -->
                    <div class="dashboard-card">
                        <h4><i class="fas fa-list-ul"></i> Active Loan Register</h4>
                        <?php if (empty($loans['active'])): ?>
                            <p class="text-muted text-center py-4">No active loans in the alliance.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-dark table-hover mb-0">
                                    <thead><tr><th>Member</th><th>Amount Owed</th><th>Status</th><th>Actions</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($loans['active'] as $loan): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($loan['character_name']) ?></td>
                                                <td class="text-danger"><?= $loan['amount_to_repay'] ?></td>
                                                <td><span class="badge bg-secondary">Active</span></td>
                                                <td>
                                                    <?php if ($state['is_leader']): ?>
                                                        <form action="/alliance/loan/forgive/<?= $loan['id'] ?>" method="POST" onsubmit="return confirm('Are you sure? This will wipe the debt.');">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                                            <button class="btn btn-sm btn-outline-danger">Forgive</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- TAB 3: ROSTER (Member Only) -->
    <?php if ($state['is_member']): ?>
        <div id="tab-roster" class="tab-content">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4><i class="fas fa-users text-neon-blue"></i> Personnel Manifest</h4>
                    <span class="text-muted">Total: <?= count($members) ?></span>
                </div>

                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle">
                        <thead>
                            <tr class="text-muted text-uppercase small">
                                <th>Name</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th class="text-end">Management</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td>
                                        <a href="/profile/<?= $member['id'] ?>" class="text-light text-decoration-none fw-bold">
                                            <?php if ($member['profile_picture_url']): ?>
                                                <img src="/serve/avatar/<?= $member['profile_picture_url'] ?>" class="rounded-circle me-2" width="24" height="24">
                                            <?php else: ?>
                                                <i class="fas fa-user-circle me-2 text-muted"></i>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($member['character_name']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php 
                                        $roleColor = match($member['role_name']) {
                                            'Leader' => 'warning',
                                            'Officer' => 'info',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $roleColor ?> bg-opacity-25 text-<?= $roleColor ?> border border-<?= $roleColor ?>">
                                            <?= htmlspecialchars($member['role_name']) ?>
                                        </span>
                                    </td>
                                    <td class="text-muted small">
                                        <?= date('M j, Y', strtotime($member['created_at'] ?? 'now')) ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($member['can_be_managed']): ?>
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    Manage
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-dark">
                                                    <?php if ($perms['can_manage_roles']): ?>
                                                        <li><h6 class="dropdown-header">Assign Role</h6></li>
                                                        <?php foreach ($roles as $role): 
                                                            if ($role->name === 'Leader') continue; 
                                                        ?>
                                                            <li>
                                                                <form action="/alliance/role/assign" method="POST">
                                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                                                    <input type="hidden" name="target_user_id" value="<?= $member['id'] ?>">
                                                                    <input type="hidden" name="role_id" value="<?= $role->id ?>">
                                                                    <button class="dropdown-item"><?= htmlspecialchars($role->name) ?></button>
                                                                </form>
                                                            </li>
                                                        <?php endforeach; ?>
                                                        <li><hr class="dropdown-divider"></li>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($perms['can_kick']): ?>
                                                        <li>
                                                            <form action="/alliance/kick/<?= $member['id'] ?>" method="POST" onsubmit="return confirm('Are you sure you want to kick this member?');">
                                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                                                <button class="dropdown-item text-danger"><i class="fas fa-ban me-2"></i> Kick Member</button>
                                                            </form>
                                                        </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- TAB 4: ADMIN / SETTINGS (Leader/Officer Only) -->
    <?php if ($state['is_leader'] || $perms['can_edit_profile']): ?>
        <div id="tab-settings" class="tab-content">
            <div class="row">
                <!-- Application Review -->
                <div class="col-md-6 mb-4">
                    <?php if ($perms['can_manage_apps']): ?>
                        <div class="dashboard-card h-100">
                            <h4><i class="fas fa-user-plus text-success"></i> Recruitment Queue</h4>
                            <?php if (empty($applications)): ?>
                                <p class="text-muted">No pending applications.</p>
                            <?php else: ?>
                                <ul class="list-group list-group-flush bg-transparent">
                                    <?php foreach ($applications as $app): ?>
                                        <li class="list-group-item bg-transparent text-light border-secondary d-flex justify-content-between align-items-center px-0">
                                            <span>
                                                <strong><?= htmlspecialchars($app->character_name) ?></strong>
                                                <br><small class="text-muted">Applied: <?= $app->created_at ?></small>
                                            </span>
                                            <div class="btn-group">
                                                <form action="/alliance/accept-app/<?= $app->id ?>" method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                                    <button class="btn btn-sm btn-success"><i class="fas fa-check"></i></button>
                                                </form>
                                                <form action="/alliance/reject-app/<?= $app->id ?>" method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                                    <button class="btn btn-sm btn-danger"><i class="fas fa-times"></i></button>
                                                </form>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Settings Form -->
                <div class="col-md-6 mb-4">
                    <div class="dashboard-card h-100">
                        <h4><i class="fas fa-cogs"></i> Alliance Settings</h4>
                        
                        <form action="/alliance/profile/edit" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Public Manifesto / Description</label>
                                <textarea name="description" class="form-control bg-dark text-light border-secondary" rows="4"><?= htmlspecialchars($alliance['description'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Update Insignia (Max 2MB)</label>
                                <input type="file" name="profile_picture" class="form-control bg-dark text-light border-secondary">
                                <?php if ($alliance['profile_picture_url']): ?>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="remove_picture" value="1" id="rm_pic">
                                        <label class="form-check-label" for="rm_pic">Remove current insignia</label>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-4">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_joinable" value="0">
                                    <input class="form-check-input" type="checkbox" name="is_joinable" value="1" id="is_joinable" <?= $alliance['is_joinable'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_joinable">
                                        Open Recruitment
                                        <div class="text-muted small">If enabled, users can join instantly without approval.</div>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Save Configuration</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
    // Inject User Resources for JS logic (Max Buttons)
    window.UserResources = {
        credits: <?= isset($advisorData['resources']->credits) ? (int)$advisorData['resources']->credits : 0 ?>
    };
</script>
<script src="/js/alliance_profile.js?v=<?= time() ?>"></script>