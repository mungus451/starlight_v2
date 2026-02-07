<?php
/**
 * Starlight Dominion - A.I. Advisor & Stats Module (Tactical Glass Monolith V2)
 *
 * EXPECTS from the main layout:
 * - $advisorData (array)
 * - $global_xp_data (array)
 * - $global_user_level (int)
 * - $active_page (string)
 */

// --- Data Mapping ---
$__stats_credits = $advisorData['resources']->credits ?? 0;
$__stats_untrained = $advisorData['resources']->untrained_citizens ?? 0;
$__stats_level = $global_user_level ?? $advisorData['stats']->level ?? 1;
$__stats_xp = $global_xp_data['current_xp'] ?? 0;
$__stats_next_lvl_xp = $global_xp_data['next_level_xp'] ?? ($__stats_xp > 0 ? $__stats_xp : 1);
$__stats_attack_turns = $advisorData['stats']->attack_turns ?? 0;
$__stats_last_updated = $advisorData['stats']->last_updated ?? null;

// --- Advice Text ---
$advice_repository = [
    'dashboard' => [
        "Central command online. Fleet status nominal.",
        "Economic output is within expected parameters.",
    ],
    'training' => [
        "Recruitment centers active. Designate unit specialization.",
        "Balance your forces: Offense wins battles, Defense holds territory.",
    ],
    'structures' => [
        "Construction protocols engaged. Upgrade essential systems.",
        "Energy output must scale with military expansion.",
    ],
    'battle' => [
        "Tactical analysis required before engagement.",
        "Victory favors the prepared commander.",
    ]
];
$active_page_key = $active_page ?? 'dashboard';
// Fallback to generic if page specific advice isn't found
$current_advice_list = $advice_repository[$active_page_key] ?? ["Systems nominal. Awaiting orders."]; 
$advice_json = htmlspecialchars(json_encode(array_values($current_advice_list)), ENT_QUOTES, 'UTF-8');

// --- XP Bar Calculation ---
$xp_progress_pct = 0;
if ($__stats_next_lvl_xp > 0) {
    $xp_progress_pct = min(100, floor(($__stats_xp / $__stats_next_lvl_xp) * 100));
}

// --- Turn Timer Calculation ---
$TURN_INTERVAL = 600; // 10 minutes
$seconds_until_next_turn = null;
if ($__stats_last_updated) {
    try {
        $last = new DateTime($__stats_last_updated, new DateTimeZone('UTC'));
        $cur  = new DateTime('now', new DateTimeZone('UTC'));
        $elapsed = max(0, $cur->getTimestamp() - $last->getTimestamp());
        $seconds_until_next_turn = $TURN_INTERVAL - ($elapsed % $TURN_INTERVAL);
    } catch (Throwable $e) { /* leave null */ }
}
$minutes_until_next_turn = $seconds_until_next_turn ? intdiv($seconds_until_next_turn, 60) : 0;
$seconds_remainder = $seconds_until_next_turn ? $seconds_until_next_turn % 60 : 0;

// --- Dominion Time (ET) ---
try {
    $__now_et = new DateTime('now', new DateTimeZone('America/New_York'));
} catch (Throwable $e) {
    $__now_et = new DateTime('now', new DateTimeZone('UTC'));
}
$__now_et_epoch = $__now_et->getTimestamp();
?>

<!-- Advisor Panel (Tactical Glass Monolith) -->
<aside class="advisor-panel" id="advisor-panel">
    
    <!-- Header: Avatar & Identity -->
    <div class="advisor-header">
        <img src="/serve/avatar/<?php echo htmlspecialchars($advisorData['user']->profile_picture_url ?? 'default.png'); ?>" alt="Advisor Avatar" class="advisor-avatar">
        <div class="advisor-player-info">
            <h3>COMMANDER</h3>
            <span class="advisor-player-level">Level <?php echo (int)$__stats_level; ?></span>
        </div>
    </div>

    <!-- Core Stats (Always Visible) -->
    <div class="advisor-core-stats">
        <div class="advisor-stat">
            <span class="advisor-stat-label">Credits</span>
            <span class="advisor-stat-value text-neon-blue" id="advisor-credits-display" data-amount="<?php echo (int)$__stats_credits; ?>">
                <?php echo number_format((int)$__stats_credits); ?>
            </span>
        </div>
        <div class="advisor-stat">
            <span class="advisor-stat-label">Turns</span>
            <span class="advisor-stat-value" id="advisor-attack-turns">
                <?php echo (int)$__stats_attack_turns; ?>
            </span>
        </div>
        <div class="advisor-stat">
            <span class="advisor-stat-label">Next Turn</span>
            <span id="next-turn-timer" class="advisor-stat-value text-accent" 
                  <?php if ($seconds_until_next_turn !== null): ?> data-seconds-until-next-turn="<?php echo (int)$seconds_until_next_turn; ?>" <?php endif; ?> 
                  data-turn-interval="600">
                <?php
                if ($seconds_until_next_turn !== null) {
                    echo sprintf('%02d:%02d', (int)$minutes_until_next_turn, (int)$seconds_remainder);
                } else {
                    echo '—';
                }
                ?>
            </span>
        </div>
        <div class="advisor-stat">
             <span class="advisor-stat-label">Time (ET)</span>
             <span id="dominion-time" class="advisor-stat-value" data-epoch="<?php echo (int)$__now_et_epoch; ?>">
                <?php echo htmlspecialchars($__now_et->format('H:i:s')); ?>
            </span>
        </div>
    </div>

    <!-- Collapsible Pod 1: Intelligence -->
    <div class="advisor-pod open"> <!-- Open by default -->
        <div class="advisor-pod-header" onclick="this.parentElement.classList.toggle('open')">
            <h4>Intelligence</h4>
            <i class="fas fa-chevron-down toggle-icon text-muted" style="font-size: 0.8rem;"></i>
        </div>
        <div class="advisor-pod-content">
            <p id="advisor-text" class="text-sm text-gray-300 italic mb-3" data-advice='<?php echo $advice_json; ?>'>
                "<?php echo $current_advice_list[0]; ?>"
            </p>
            
            <!-- XP Progress -->
            <div class="text-xs flex justify-between text-muted mb-1 uppercase tracking-wide">
                <span>XP Progress</span>
                <span><?php echo (int)$xp_progress_pct; ?>%</span>
            </div>
            <div class="w-full bg-gray-800 h-1 rounded-full overflow-hidden">
                <div class="bg-cyan-500 h-full shadow-[0_0_10px_rgba(0,243,255,0.5)]" style="width: <?php echo (int)$xp_progress_pct; ?>%"></div>
            </div>
        </div>
    </div>

    <!-- Collapsible Pod 2: Population -->
    <div class="advisor-pod">
        <div class="advisor-pod-header" onclick="this.parentElement.classList.toggle('open')">
            <h4>Population</h4>
            <i class="fas fa-chevron-down toggle-icon text-muted" style="font-size: 0.8rem;"></i>
        </div>
        <div class="advisor-pod-content">
            <div class="advisor-stat border-0 p-0">
                <span class="advisor-stat-label">Untrained</span>
                <span class="advisor-stat-value text-white" id="advisor-untrained-display">
                    <?php echo number_format((int)$__stats_untrained); ?>
                </span>
            </div>
        </div>
    </div>

</aside>

<!-- Timer Logic -->
<script>
(function(){
    // Next Turn Timer
    const elNextTurn  = document.getElementById('next-turn-timer');
    if (elNextTurn) {
        const attrSecs = elNextTurn.getAttribute('data-seconds-until-next-turn');
        if (attrSecs) {
            let seconds = parseInt(attrSecs, 10);
            const interval = setInterval(() => {
                if (seconds <= 0) {
                    clearInterval(interval);
                    elNextTurn.textContent = "READY";
                    elNextTurn.classList.add('text-neon-blue');
                    return;
                }
                seconds--;
                const m = Math.floor(seconds / 60);
                const s = seconds % 60;
                elNextTurn.textContent = (m < 10 ? '0' + m : m) + ':' + (s < 10 ? '0' + s : s);
            }, 1000);
        }
    }

    // Dominion Time Clock
    const elDomTime = document.getElementById('dominion-time');
    let domEpoch = elDomTime ? parseInt(elDomTime.getAttribute('data-epoch') || '0', 10) : 0;
    if (domEpoch > 0) {
        setInterval(function(){
            domEpoch += 1;
            const d = new Date(domEpoch * 1000);
            const formatted = new Intl.DateTimeFormat('en-GB', {
                timeZone: 'America/New_York',
                hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit'
            }).format(d);
            elDomTime.textContent = formatted;
        }, 1000);
    }
    
    // Advice Rotation
    const adviceText = document.getElementById('advisor-text');
    if (adviceText) {
        try {
            const messages = JSON.parse(adviceText.getAttribute('data-advice'));
            if (messages.length > 1) {
                let i = 0;
                setInterval(() => {
                    i = (i + 1) % messages.length;
                    adviceText.style.opacity = 0;
                    setTimeout(() => {
                        adviceText.textContent = '"' + messages[i] + '"';
                        adviceText.style.opacity = 1;
                    }, 500);
                }, 8000);
            }
        } catch(e){}
    }
})();
</script>