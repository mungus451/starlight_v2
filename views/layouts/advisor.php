<?php
/**
 * Starlight Dominion - A.I. Advisor & Stats Module (DROP-IN)
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
        "Your central command hub. Monitor your resources and fleet status from here.",
        "A strong economy is the backbone of any successful empire.",
    ],
    'training' => [
        "Train your untrained citizens into specialized units to expand your dominion.",
        "Workers increase your income, while Soldiers and Guards form your military might.",
    ],
    // Add other pages as needed
];
$active_page_key = $active_page ?? 'dashboard';
$current_advice_list = $advice_repository[$active_page_key] ?? ["Welcome to Starlight Dominion."];
$advice_json = htmlspecialchars(json_encode(array_values($current_advice_list)), ENT_QUOTES, 'UTF-8');

// --- XP Bar Calculation ---
$xp_progress_pct = 0;
if ($__stats_next_lvl_xp > 0) {
    $xp_progress_pct = min(100, floor(($__stats_xp / $__stats_next_lvl_xp) * 100));
}

// --- Turn Timer Calculation ---
$TURN_INTERVAL = 600; // 10 minutes from example
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
<!-- Advisor -->
<div class="content-box rounded-lg p-4 advisor-container">
    <h3 class="font-title text-cyan-400 border-b border-gray-600 pb-2 mb-2">A.I. Advisor</h3>
    <button id="toggle-advisor-btn" class="mobile-only-button">-</button>

    <div id="advisor-content">
        <p id="advisor-text" class="text-sm transition-opacity duration-500" data-advice='<?php echo $advice_json; ?>'>
            <?php echo $current_advice_list[0]; ?>
        </p>

        <div class="mt-3 pt-3 border-t border-gray-600">
            <div class="flex justify-between text-xs mb-1">
                <span id="advisor-level-display" class="text-white font-semibold">Level <?php echo (int)$__stats_level; ?> Progress</span>
                <span id="advisor-xp-display" class="text-gray-400"><?php echo number_format((int)$__stats_xp) . ' / ' . number_format((int)$__stats_next_lvl_xp); ?> XP</span>
            </div>
            <div class="w-full bg-gray-700 rounded-full h-2.5" title="<?php echo (int)$xp_progress_pct; ?>%">
                <div id="advisor-xp-bar" class="bg-cyan-500 h-2.5 rounded-full" style="width: <?php echo (int)$xp_progress_pct; ?>%"></div>
            </div>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="content-box rounded-lg p-4 stats-container">
    <h3 class="font-title text-cyan-400 border-b border-gray-600 pb-2 mb-3">Stats</h3>
    <button id="toggle-stats-btn" class="mobile-only-button">-</button>

    <div id="stats-content">
        <ul class="space-y-2 text-sm">
            <li class="flex justify-between">
                <span>Credits:</span>
                <span id="advisor-credits-display" class="text-white font-semibold" data-amount="<?php echo (int)$__stats_credits; ?>">
                    <?php echo number_format((int)$__stats_credits); ?>
                </span>
            </li>

            <li class="flex justify-between">
                <span>Untrained Citizens:</span>
                <span id="advisor-untrained-display" class="text-white font-semibold"><?php echo number_format((int)$__stats_untrained); ?></span>
            </li>
            
            <li class="flex justify-between">
                <span>Level:</span>
                <span id="advisor-level-value" class="text-white font-semibold"><?php echo (int)$__stats_level; ?></span>
            </li>

            <li class="flex justify-between">
                <span>Attack Turns:</span>
                <span id="advisor-attack-turns" class="text-white font-semibold"><?php echo (int)$__stats_attack_turns; ?></span>
            </li>

            <li class="flex justify-between border-t border-gray-600 pt-2 mt-2">
                <span>Next Turn In:</span>
                <span id="next-turn-timer" class="text-cyan-300 font-bold" <?php if ($seconds_until_next_turn !== null): ?> data-seconds-until-next-turn="<?php echo (int)$seconds_until_next_turn; ?>" <?php endif; ?> data-turn-interval="600">
                    <?php
                    if ($seconds_until_next_turn !== null) {
                        echo sprintf('%02d:%02d', (int)$minutes_until_next_turn, (int)$seconds_remainder);
                    } else {
                        echo '—';
                    }
                    ?>
                </span>
            </li>

            <li class="flex justify-between">
                <span>Dominion Time (ET):</span>
                <span id="dominion-time" class="text-white font-semibold" data-epoch="<?php echo (int)$__now_et_epoch; ?>" data-tz="America/New_York">
                    <?php echo htmlspecialchars($__now_et->format('H:i:s')); ?>
                </span>
            </li>
        </ul>
    </div>
</div>

<script>
// Stripped-down JS for timers from the example file
(function(){
    const elNextTurn  = document.getElementById('next-turn-timer');
    if (elNextTurn) {
        const attrSecs = elNextTurn.getAttribute('data-seconds-until-next-turn');
        if (attrSecs) {
            let seconds = parseInt(attrSecs, 10);
            const interval = setInterval(() => {
                if (seconds <= 0) {
                    clearInterval(interval);
                    elNextTurn.textContent = "Ready";
                    return;
                }
                seconds--;
                const m = Math.floor(seconds / 60);
                const s = seconds % 60;
                elNextTurn.textContent = (m < 10 ? '0' + m : m) + ':' + (s < 10 ? '0' + s : s);
            }, 1000);
        }
    }

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
})();
</script>