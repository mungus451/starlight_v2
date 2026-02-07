<?php
// --- PAGE CONFIGURATION ---
$page_title = 'Training & Fleet Management';
$active_page = 'battle.php';
$ROOT = dirname(__DIR__, 2);
$user_id = (int)$_SESSION['id'];

// --- TABS ---
$current_tab = 'train';
if (isset($_GET['tab'])) {
    $t = $_GET['tab'];
    if ($t === 'disband') $current_tab = 'disband';
    elseif ($t === 'recovery') $current_tab = 'recovery';
}

// --- SESSION AND DATABASE SETUP ---
require_once $ROOT . '/config/config.php';
require_once $ROOT . '/src/Services/StateService.php';
require_once $ROOT . '/config/balance.php';
require_once $ROOT . '/template/includes/advisor_hydration.php';
require_once $ROOT . '/src/Game/GameData.php';

// --- Data Hydration ---
require_once $ROOT . '/src/Repositories/TrainingRepository.php'; 
$repo = new TrainingRepository($link); 
$page_data = $repo->getTrainingPageData($user_id);
extract($page_data);

// --- FORM SUBMISSION HANDLING & CSRF TOKEN---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once $ROOT . '/src/Controllers/TrainingController.php';
    exit;
}
$csrf_token = generate_csrf_token();

// --- INCLUDE UNIVERSAL HEADER---

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

global $link;

$user_id = (int)($_SESSION['id'] ?? 0);

if (!$link || !($link instanceof \mysqli) || !@mysqli_ping($link)) {
    // If $link is bad or closed, we must reconnect.
    // We use the constants defined in config/config.php
    $link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    // Check if the reconnect failed
    if ($link === false) {
        // We can't proceed.
        die("Critical Error: Database connection was closed and could not be re-established.");
    }
    
    // We must set the timezone, just like config.php does
    mysqli_query($link, "SET time_zone = '+00:00'");
}

if ($user_id <= 0) {
    // This prevents errors for logged-out users if this header is
    // accidentally included on a public page that doesn't require login.
    // We'll die here to prevent the fatal error from ss_process_and_get_user_state.
    die('Critical Error: User ID not found in session for header.php.');
}

//--- DATA FETCHING ---
$needed_fields = [
    'credits','level','experience',
    'soldiers','guards','sentries','spies','workers',
    'armory_level','charisma_points', 'gemstones',
    'last_updated','attack_turns','untrained_citizens',
    'strength_points', 'constitution_points', 'wealth_points', 'dexterity_points',
    'fortification_level', 'offense_upgrade_level', 'defense_upgrade_level',
    'economy_upgrade_level', 'population_level'
];
// This line is now safe, as $link is guaranteed to be a live connection.
$user_stats = ss_process_and_get_user_state($link, $user_id, $needed_fields);
?>
<!DOCTYPE html>
<html lang="en" x-data="{ panels: { eco:true, mil:true, pop:true, fleet:true, sec:true, esp:true, structure: true, deposit: true, withdraw: true, transfer: true, history: true } }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Starlight Dominion - <?php echo htmlspecialchars($page_title ?? 'Game'); ?></title>
    
    <?php if (isset($csrf_token)): ?>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf_token); ?>">
    <?php endif; ?>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Favicon Attachments -->
    <link rel="icon" type="image/avif" href="/assets/img/favicon.avif">
    <link rel="icon" type="image/png" href="/assets/img/favicon.png" sizes="32x32">


    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>[x-cloak]{display:none!important}</style>

<!-- Google Adsense Code -->
<?php include __DIR__ . '/adsense.php'; ?>

</head>
<body class="text-gray-400 antialiased">
    <div class="min-h-screen bg-cover bg-center bg-fixed" style="background-image: url('/assets/img/backgroundAlt.avif');">
        <div class="container mx-auto p-4 md:p-8">
 

      <?      
/**
 * /template/includes/navigation.php
 *
 * Included from header.php
 * Uses mysqli (global $link) and the logged-in user id from session.
 */

global $link;
$user_id = (int)($_SESSION['id'] ?? 0);

if ($user_id <= 0) {
    // Should not happen when logged in, but fail-safe.
    return;
}

/**
 * Pull nav-level user data.
 * Triple-checked against 1031.sql:
 *   - alliances table has: id, name, tag, avatar_path ...
 *   - users table has: alliance_id
 * so we can safely join and select a.tag as alliance_tag.
 */
$stmt = $link->prepare(
    "SELECT 
        u.character_name,
        u.credits,
        u.gemstones,
        u.avatar_path,
        a.id   AS alliance_id,
        a.name AS alliance_name,
        a.tag  AS alliance_tag
     FROM users u
     LEFT JOIN alliances a ON u.alliance_id = a.id
     WHERE u.id = ?"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$nav_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

/**
 * IMPORTANT:
 * We prefix everything with _nav to avoid overriding
 * page-level/profile-level variables such as $alliance_name
 * that are set by view-profile hydration code.
 */
$_nav_character_name = htmlspecialchars($nav_user['character_name'] ?? 'Player', ENT_QUOTES, 'UTF-8');
$_nav_avatar_path    = htmlspecialchars($nav_user['avatar_path'] ?? '/assets/img/human.avif', ENT_QUOTES, 'UTF-8');
$_nav_alliance_id    = (int)($nav_user['alliance_id'] ?? 0);
$_nav_alliance_name  = htmlspecialchars($nav_user['alliance_name'] ?? 'No Alliance', ENT_QUOTES, 'UTF-8');
$_nav_alliance_tag   = htmlspecialchars($nav_user['alliance_tag'] ?? '', ENT_QUOTES, 'UTF-8');
$_nav_credits        = (int)($nav_user['credits'] ?? 0);
$_nav_gemstones      = (int)($nav_user['gemstones'] ?? 0);

/**
 * Navigation structure
 */
$main_nav_links = [
    'HOME'       => '/dashboard.php',
    'BATTLE'     => '/battle.php',
    'STRUCTURES' => '/structures.php',
    'ALLIANCE'   => '/alliance.php',
    'COMMUNITY'  => '/community.php',
    'TUTORIAL'   => '/tutorial.php',
    'SIGN OUT'   => '/auth.php?action=logout',
];

$sub_nav_links = [
    'HOME' => [
        'Dashboard'         => '/dashboard.php',
        'Bank'              => '/bank.php',
        'Levels'            => '/levels.php',
        'Currency Converter'=> '/converter.php',
        'Profile'           => '/profile.php',
        'Settings'          => '/settings.php',
    ],
    'BATTLE' => [
        'Attack'        => '/attack.php',
        'Training'      => '/battle.php',
        'Spy'           => '/spy.php',
        'Armory'        => '/armory.php',
        'Auto Recruiter'=> '/auto_recruit.php',
        'War History'   => '/war_history.php',
        'Spy History'   => '/spy_history.php',
    ],
    'ALLIANCE' => [
        'Alliance Hub'      => '/alliance.php',
        'Bank'              => '/alliance_bank.php',
        'Structures'        => '/alliance_structures.php',
        'Forum'             => '/alliance_forum.php',
        'Diplomacy'         => '/diplomacy.php',
        'Roles & Permissions'=> '/alliance_roles.php',
        'War'               => '/war_declaration.php',
    ],
    'STRUCTURES' => [
        'Currency Converter' => '/converter.php',
        'COSMIC ROLL'        => '/cosmic_roll.php',
        'QUANTUM ROULETTE'   => '/quantum_roulette.php',
    ],
    'COMMUNITY' => [
        'News'            => '/community.php',
        'Leaderboards'    => '/stats.php',
        'War Leaderboard' => '/war_leaderboard.php',
        'Discord'         => 'https://discord.gg/sCKvuxHAqt',
    ],
];

/**
 * Third-level submenu for war pages
 */
$sub_sub_nav_links = [
    'WAR' => [
        'War Declaration' => '/war_declaration.php',
        'Realm War'       => '/realm_war.php',
        'War Archives'    => '/alliance_war_history.php',
    ],
];

/**
 * Active detection
 * $active_page is expected to be set by the page before including header/nav.
 */
$active_main_category = 'HOME';
$active_page_path     = '/' . $active_page;

if (in_array($active_page, ['battle.php','attack.php','war_history.php','spy_history.php','armory.php','auto_recruit.php','spy.php'], true)) {
    $active_main_category = 'BATTLE';
} elseif (in_array($active_page, [
    'alliance.php','create_alliance.php','edit_alliance.php','alliance_roles.php',
    'alliance_bank.php','alliance_transfer.php','alliance_structures.php',
    'alliance_forum.php','create_thread.php','view_thread.php','diplomacy.php',
    'war_declaration.php','view_alliances.php','view_alliance.php','realm_war.php','alliance_war_history.php',
], true)) {
    $active_main_category = 'ALLIANCE';
} elseif (in_array($active_page, ['structures.php','cosmic_roll.php','quantum_roulette.php'], true)) {
    $active_main_category = 'STRUCTURES';
} elseif (in_array($active_page, ['community.php','stats.php','war_leaderboard.php'], true)) {
    $active_main_category = 'COMMUNITY';
}

/**
 * If we are on a WAR-ish page, show the third level
 */
$active_sub_category = null;
if (in_array($active_page, [
    'war_declaration.php','view_alliances.php','view_alliance.php','realm_war.php','alliance_war_history.php','diplomacy.php'
], true)) {
    $active_sub_category = 'WAR';
}
?>
<header class="text-center mb-4">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <h1 class="text-5xl font-title text-cyan-400" style="text-shadow: 0 0 8px rgba(6, 182, 212, 0.7);">STARLIGHT DOMINION</h1>
</header>

<div class="main-bg border border-gray-700 rounded-lg shadow-2xl p-1">
    <div class="flex justify-center flex-wrap items-center gap-x-4 gap-y-1 md:gap-x-6 bg-gray-900 p-2 rounded-t-md">
        <div class="flex items-center" title="Credits">
            <span class="font-bold text-yellow-400 mr-2">Credits:</span>
            <span id="credits-display" class="font-bold text-white">
                <?php echo number_format($_nav_credits); ?>
            </span>
        </div>

        <div class="flex items-center" title="Gemstones">
            <span class="font-bold text-purple-400 mr-2">Gemstones:</span>
            <span id="gemstones-display" class="font-bold text-white">
                <?php echo number_format($_nav_gemstones); ?>
            </span>
        </div>
    </div>

    <nav class="flex justify-center flex-wrap items-center gap-x-2 gap-y-1 md:gap-x-6 bg-gray-900 p-2">
        <?php foreach ($main_nav_links as $title => $url): ?>
            <a href="<?php echo $url; ?>"
               class="nav-link <?php echo ($title === $active_main_category) ? 'active font-bold' : 'text-gray-400 hover:text-white'; ?> px-2 md:px-3 py-1 transition-all text-sm md:text-base">
                <?php echo $title; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if (isset($sub_nav_links[$active_main_category]) && !empty($sub_nav_links[$active_main_category])): ?>
        <div class="bg-gray-800 text-center p-2 flex justify-center flex-wrap gap-x-4 gap-y-1">
            <?php foreach ($sub_nav_links[$active_main_category] as $title => $url):
                $is_external  = filter_var($url, FILTER_VALIDATE_URL);
                $is_active_sub = ($url === $active_page_path)
                    || ($title === 'War' && in_array($active_page, [
                        'war_declaration.php','view_alliances.php','view_alliance.php',
                        'realm_war.php','alliance_war_history.php','diplomacy.php'
                    ], true));
            ?>
                <a href="<?php echo $url; ?>"
                   <?php if ($is_external) echo 'target="_blank" rel="noopener noreferrer"'; ?>
                   class="<?php echo $is_active_sub ? 'font-semibold text-white' : 'text-gray-400 hover:text-white'; ?> px-3">
                    <?php echo $title; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($active_sub_category && isset($sub_sub_nav_links[$active_sub_category])): ?>
        <div class="bg-gray-700 text-center p-2 flex justify-center flex-wrap gap-x-4 gap-y-1">
            <?php foreach ($sub_sub_nav_links[$active_sub_category] as $title => $url):
                $is_external    = filter_var($url, FILTER_VALIDATE_URL);
                $is_active_sub2 = ($url === $active_page_path);
            ?>
                <a href="<?php echo $url; ?>"
                   <?php if ($is_external) echo 'target="_blank" rel="noopener noreferrer"'; ?>
                   class="<?php echo $is_active_sub2 ? 'font-semibold text-white' : 'text-gray-300 hover:text-white'; ?> px-3">
                    <?php echo $title; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>


            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 p-4">

<aside class="lg:col-span-1 space-y-4">
   <?
/**
 * Starlight Dominion - A.I. Advisor & Stats Module (DROP-IN)
 *
 * Expects (when available) from parent page:
 * - $active_page (string)
 * - $user_stats (array) OR $me (attack page) providing at least credits/level/xp/attack_turns/last_updated
 * - $user_xp (int)
 * - $user_level (int)
 * - $minutes_until_next_turn (int)
 * - $seconds_remainder (int)
 * - $now (DateTime, UTC)
 */

// Determine the stats source gracefully (dashboard uses $user_stats; attack.php often has $me)
$__stats = [];
if (isset($user_stats) && is_array($user_stats)) {
    $__stats = $user_stats;
} elseif (isset($me) && is_array($me)) {
    $__stats = $me;
}

// Advice copy (includes pages from your snippet)
$advice_repository = [
    'dashboard.php' => [
        "Your central command hub. Monitor your resources and fleet status from here.",
        "A strong economy is the backbone of any successful empire.",
        "Keep an eye on your Dominion Time; it's synchronized across the galaxy."
    ],
    'attack.php' => [
        "Choose your targets wisely. Attacking stronger opponents yields greater rewards, but carries higher risk.",
        "The more turns you use in an attack, the more credits you can plunder on a victory.",
        "Check a target's level. A higher level may indicate a more formidable opponent."
    ],
    'spy.php' => [
        "Intelligence is key. Use spy missions to gain an advantage over your opponents.",
        "A successful assassination can cripple an opponent's economy or military.",
        "Sabotage missions can weaken an empire's foundations, making them vulnerable to attack."
    ],
    'battle.php' => [
        "Train your untrained citizens into specialized units to expand your dominion.",
        "Workers increase your income, while Soldiers and Guards form your military might.",
        "Don't forget to balance your army. A strong offense is nothing without a solid defense."
    ],
    'levels.php' => [
        "Spend proficiency points to permanently enhance your dominion's capabilities.",
        "Strength increases your fleet's Offense Power in battle.",
        "Constitution boosts your Defense Rating, making you a harder target."
    ],
    'war_history.php' => [
        "Review your past engagements to learn from victories and defeats.",
        "Analyze your defense logs to identify your most frequent attackers.",
        "A victory is sweet, but a lesson learned from defeat is invaluable."
    ],
    'structures.php' => [
        "This is where you can spend points to upgrade your core units.",
        "Upgrading soldiers will make your attacks more potent.",
        "Investing in guards will bolster your empire's defenses."
    ],
    'profile.php' => [
        "Express yourself. Your avatar and biography are visible to other commanders.",
        "A picture is worth a thousand words, or in this galaxy, a thousand credits.",
        "Remember to save your changes after updating your profile."
    ],
    'settings.php' => [
        "Secure your account by regularly changing your password.",
        "Vacation mode protects your empire from attacks while you are away. Use it wisely.",
        "Account settings are critical. Double-check your entries before saving."
    ],
    'bank.php' => [
        "Store your credits in the bank to keep them safe from plunder. Banked credits cannot be stolen.",
        "You have a limited number of deposits each day. Plan your finances carefully.",
        "Remember to withdraw credits before you can spend them on units or structures."
    ],
    'community.php' => [
        "Join our Discord to stay up-to-date with the latest game news and announcements.",
        "Community is key. Share your strategies and learn from fellow commanders.",
        "Your feedback during this development phase is invaluable to us."
    ],
    'inspiration.php' => [
        "Greatness is built upon the foundations laid by others. It's always good to acknowledge our roots.",
        "Exploring open-source projects is a great way to learn and contribute to the community.",
        "Every great game has a story. This one is no different."
    ],
];

$current_advice_list = isset($advice_repository[$active_page]) ? $advice_repository[$active_page] : ["Welcome to Starlight Dominion."];
$advice_json = htmlspecialchars(json_encode(array_values($current_advice_list)), ENT_QUOTES, 'UTF-8');

// XP bar
$xp_for_next_level = 0;
$xp_progress_pct   = 0;
if (isset($user_xp, $user_level)) {
    $lvl = (int)$user_level;
    $xp_for_next_level = floor(1000 * pow($lvl, 1.5));
    $xp_progress_pct   = $xp_for_next_level > 0 ? min(100, floor(((int)$user_xp / $xp_for_next_level) * 100)) : 100;
}

// Turn timer hydrate:
// Prefer explicit minutes/seconds. If absent, derive from last_updated (server says turn math is correct).
$TURN_INTERVAL = 600; // 10 minutes
$seconds_until_next_turn = null;

if (isset($minutes_until_next_turn, $seconds_remainder) && is_numeric($minutes_until_next_turn) && is_numeric($seconds_remainder)) {
    $seconds_until_next_turn = ((int)$minutes_until_next_turn * 60) + (int)$seconds_remainder;
} else {
    try {
        if (!empty($__stats['last_updated'])) {
            $last = new DateTime($__stats['last_updated'], new DateTimeZone('UTC'));
            $cur  = (isset($now) && $now instanceof DateTime) ? $now : new DateTime('now', new DateTimeZone('UTC'));
            $elapsed = max(0, $cur->getTimestamp() - $last->getTimestamp());
            $seconds_until_next_turn = $TURN_INTERVAL - ($elapsed % $TURN_INTERVAL);
        }
    } catch (Throwable $e) { /* leave null */ }
}
if ($seconds_until_next_turn !== null) {
    $seconds_until_next_turn = max(0, min($TURN_INTERVAL, (int)$seconds_until_next_turn));
    $minutes_until_next_turn = intdiv($seconds_until_next_turn, 60);
    $seconds_remainder       = $seconds_until_next_turn % 60;
}

// Dominion Time (server-anchored, display only). If $now provided, honor it; otherwise use ET "now".
try {
    if (isset($now) && $now instanceof DateTime) {
        $nowEt = clone $now;
        $nowEt->setTimezone(new DateTimeZone('America/New_York'));
        $__now_et = $nowEt;
    } else {
        $__now_et = new DateTime('now', new DateTimeZone('America/New_York'));
    }
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

        <?php if (isset($user_xp, $user_level)): ?>
        <div class="mt-3 pt-3 border-t border-gray-600">
            <div class="flex justify-between text-xs mb-1">
                <span id="advisor-level-display" class="text-white font-semibold">Level <?php echo (int)$user_level; ?> Progress</span>
                <span id="advisor-xp-display" class="text-gray-400"><?php echo number_format((int)$user_xp) . ' / ' . number_format((int)$xp_for_next_level); ?> XP</span>
            </div>
            <div class="w-full bg-gray-700 rounded-full h-2.5" title="<?php echo (int)$xp_progress_pct; ?>%">
                <div id="advisor-xp-bar" class="bg-cyan-500 h-2.5 rounded-full" style="width: <?php echo (int)$xp_progress_pct; ?>%"></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Stats -->
<div class="content-box rounded-lg p-4 stats-container">
    <h3 class="font-title text-cyan-400 border-b border-gray-600 pb-2 mb-3">Stats</h3>
    <button id="toggle-stats-btn" class="mobile-only-button">-</button>

    <div id="stats-content">
        <ul class="space-y-2 text-sm">
            <?php if(isset($__stats['credits'])): ?>
                <li class="flex justify-between">
                    <span>Credits:</span>
                    <span id="advisor-credits-display" class="text-white font-semibold" data-amount="<?php echo (int)$__stats['credits']; ?>">
                        <?php echo number_format((int)$__stats['credits']); ?>
                    </span>
                </li>
            <?php endif; ?>

            <?php if(isset($__stats['banked_credits'])): ?>
                <li class="flex justify-between">
                    <span>Banked Credits:</span>
                    <span class="text-white font-semibold"><?php echo number_format((int)$__stats['banked_credits']); ?></span>
                </li>
            <?php endif; ?>

            <?php $untrained_ready = isset($__stats['untrained_citizens']) ? (int)$__stats['untrained_citizens'] : null; ?>
            <?php if($untrained_ready !== null): ?>
                <li class="flex justify-between">
                    <span>Untrained Citizens (ready):</span>
                    <span id="advisor-untrained-display" class="text-white font-semibold"><?php echo number_format($untrained_ready); ?></span>
                </li>
            <?php endif; ?>

            <?php if(isset($__stats['level'])): ?>
                <li class="flex justify-between">
                    <span>Level:</span>
                    <span id="advisor-level-value" class="text-white font-semibold"><?php echo (int)$__stats['level']; ?></span>
                </li>
            <?php endif; ?>

            <?php if(isset($__stats['attack_turns'])): ?>
                <li class="flex justify-between">
                    <span>Attack Turns:</span>
                    <span id="advisor-attack-turns" class="text-white font-semibold"><?php echo (int)$__stats['attack_turns']; ?></span>
                </li>
            <?php endif; ?>

            <li class="flex justify-between border-t border-gray-600 pt-2 mt-2">
                <span>Next Turn In:</span>
                <span
                    id="next-turn-timer"
                    class="text-cyan-300 font-bold"
                    <?php if ($seconds_until_next_turn !== null): ?>
                        data-seconds-until-next-turn="<?php echo (int)$seconds_until_next_turn; ?>"
                    <?php endif; ?>
                    data-turn-interval="600"
                >
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
                <span id="dominion-time"
                      class="text-white font-semibold"
                      data-epoch="<?php echo (int)$__now_et_epoch; ?>"
                      data-tz="America/New_York">
                    <?php echo htmlspecialchars($__now_et->format('H:i:s')); ?>
                </span>
            </li>
        </ul>
    </div>
</div>

<script>
(function(){
    // ELEMENTS
    const elCredits   = document.getElementById('advisor-credits-display');
    const elUntrained = document.getElementById('advisor-untrained-display');
    const elTurns     = document.getElementById('advisor-attack-turns');
    const elNextTurn  = document.getElementById('next-turn-timer');
    const elDomTime   = document.getElementById('dominion-time');

    // -------------------------------
    // DOMINION CLOCK (display only; optional re-anchor via API)
    // -------------------------------
    let domEpoch = elDomTime ? parseInt(elDomTime.getAttribute('data-epoch') || '0', 10) : 0;
    const tz = elDomTime ? (elDomTime.getAttribute('data-tz') || 'America/New_York') : 'America/New_York';

    function renderDomTime(epoch){
        if (!elDomTime) return;
        const d = new Date(epoch * 1000);
        const formatted = new Intl.DateTimeFormat('en-GB', {
            timeZone: tz,
            hour12: false,
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        }).format(d);
        elDomTime.textContent = formatted;
    }

    if (domEpoch > 0) {
        setInterval(function(){
            domEpoch += 1;
            renderDomTime(domEpoch);
        }, 1000);
        renderDomTime(domEpoch);
    }

    // ---------------------------------------------------
    // COUNTDOWN (incorporated & upgraded): auto-cycling, monotonic, AJAX-agnostic
    // ---------------------------------------------------
    // hydrate once from server; then:
    // remaining = (initialSeconds - elapsed) mod TURN_INTERVAL
    if (!window.__advisorCountdownInit) {
        window.__advisorCountdownInit = true;

        if (elNextTurn) {
            const attrSecs = elNextTurn.getAttribute('data-seconds-until-next-turn');
            const initialSeconds = attrSecs ? parseInt(attrSecs, 10) : NaN;

            const attrInterval = elNextTurn.getAttribute('data-turn-interval');
            const TURN_INTERVAL = (attrInterval && !isNaN(parseInt(attrInterval, 10)))
                ? Math.max(1, parseInt(attrInterval, 10))
                : 600;

            if (Number.isFinite(initialSeconds) && initialSeconds >= 0) {
                const havePerf = (typeof performance !== 'undefined' && typeof performance.now === 'function');
                const startMono = havePerf ? performance.now() : Date.now();

                const fmt = (secs) => {
                    secs = Math.max(0, Math.floor(secs));
                    const m = Math.floor(secs / 60);
                    const s = secs % 60;
                    return (m < 10 ? '0' + m : '' + m) + ':' + (s < 10 ? '0' + s : '' + s);
                };

                let lastWhole = -1;

                function tick(){
                    const nowMono = havePerf ? performance.now() : Date.now();
                    const elapsed = (nowMono - startMono) / 1000;

                    // continuous wrap into [0, TURN_INTERVAL)
                    let remaining = initialSeconds - elapsed;
                    remaining = ((remaining % TURN_INTERVAL) + TURN_INTERVAL) % TURN_INTERVAL;

                    const whole = Math.floor(remaining);

                    if (whole !== lastWhole) {
                        lastWhole = whole;

                        // Optional visual cue at 00:00
                        if (whole === 0) {
                            elNextTurn.classList.add('turn-ready');
                            setTimeout(() => elNextTurn.classList.remove('turn-ready'), 900);
                        }
                        elNextTurn.textContent = fmt(whole);
                    }
                    requestAnimationFrame(tick);
                }
                requestAnimationFrame(tick);
            } else {
                elNextTurn.textContent = '—';
            }
        }
    }

    // -----------------------------------------------
    // 10s POLL: STATS ONLY (does not touch countdown)
    // -----------------------------------------------
    /* async function pollAdvisor(){
        try {
            const res = await fetch('/api/advisor_poll.php', {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            });
            if (!res.ok) return;
            const data = await res.json();

            if (elCredits && typeof data.credits === 'number') {
                elCredits.textContent = new Intl.NumberFormat().format(data.credits);
                elCredits.setAttribute('data-amount', String(data.credits));
            }
            if (elUntrained && typeof data.untrained_citizens === 'number') {
                elUntrained.textContent = new Intl.NumberFormat().format(data.untrained_citizens);
            }
            if (elTurns && typeof data.attack_turns === 'number') {
                elTurns.textContent = String(data.attack_turns);
            }
            // Allow clock re-anchor if backend includes it
            if (elDomTime && typeof data.server_time_unix === 'number') {
                domEpoch = parseInt(data.server_time_unix, 10);
                renderDomTime(domEpoch);
            }
        } catch (_) { /* silent */ }
    }

    /*pollAdvisor(); */
    /*setInterval(pollAdvisor, 10000); */
})();
</script>
    
</aside>

<main class="lg:col-span-3 space-y-4">

    <!-- /template/includes/training/top_card.php -->
    <?php if(isset($_SESSION['training_message'])): ?>
        <div class="bg-cyan-900 border border-cyan-500/50 text-cyan-300 p-3 rounded-md text-center">
            <?php echo htmlspecialchars($_SESSION['training_message']); unset($_SESSION['training_message']); ?>
        </div>
    <?php endif; ?>
    <?php if(isset($_SESSION['training_error'])): ?>
        <div class="bg-red-900 border border-red-500/50 text-red-300 p-3 rounded-md text-center">
            <?php echo htmlspecialchars($_SESSION['training_error']); unset($_SESSION['training_error']); ?>
        </div>
    <?php endif; ?>

    <div class="content-box rounded-lg p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
            <div>
                <p class="text-xs uppercase">Citizens</p>
                <p id="available-citizens" data-amount="<?php echo $user_stats['untrained_citizens']; ?>" class="text-lg font-bold text-white">
                    <?php echo number_format($user_stats['untrained_citizens']); ?>
                </p>
            </div>
            <div>
                <p class="text-xs uppercase">Credits</p>
                <p id="available-credits" data-amount="<?php echo $user_stats['credits']; ?>" class="text-lg font-bold text-white">
                    <?php echo number_format($user_stats['credits']); ?>
                </p>
            </div>
            <div>
                <p class="text-xs uppercase">Total Cost</p>
                <p id="total-build-cost" class="text-lg font-bold text-yellow-400">0</p>
            </div>
            <div>
                <p class="text-xs uppercase">Total Refund</p>
                <p id="total-refund-value" class="text-lg font-bold text-green-400">0</p>
            </div>
        </div>
    </div>
    
    <div class="border-b border-gray-600">
        <nav class="flex space-x-2" aria-label="Tabs">
            <?php
                $train_btn_classes   = ($current_tab === 'train')    ? 'bg-gray-700 text-white font-semibold' : 'bg-gray-800 hover:bg-gray-700 text-gray-400';
                $disband_btn_classes = ($current_tab === 'disband')  ? 'bg-gray-700 text-white font-semibold' : 'bg-gray-800 hover:bg-gray-700 text-gray-400';
                $recovery_btn_classes= ($current_tab === 'recovery') ? 'bg-gray-700 text-white font-semibold' : 'bg-gray-800 hover:bg-gray-700 text-gray-400';
            ?>
            <button id="train-tab-btn" class="tab-btn <?php echo $train_btn_classes; ?> py-3 px-6 rounded-t-lg text-base transition-colors">Train Units</button>
            <button id="disband-tab-btn" class="tab-btn <?php echo $disband_btn_classes; ?> py-3 px-6 rounded-t-lg text-base transition-colors">Disband Units</button>
            <button id="recovery-tab-btn" class="tab-btn <?php echo $recovery_btn_classes; ?> py-3 px-6 rounded-t-lg text-base transition-colors">Recovery Queue</button>
        </nav>
    </div>

    <!-- TRAIN TAB -->

    <!-- /template/includes/training/train_tab.php -->
<div id="train-tab-content" class="<?php if ($current_tab !== 'train') echo 'hidden'; ?>">
    <form id="train-form" action="/battle.php" method="POST" class="space-y-4" data-charisma-discount="<?php echo $charisma_discount; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="action" value="train">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach($unit_costs as $unit => $cost): 
                $discounted_cost = floor($cost * $charisma_discount);
            ?>
            <div class="content-box rounded-lg p-3">
                <div class="flex items-center space-x-3">
                    <img src="/assets/img/<?php echo strtolower($unit_names[$unit]); ?>.avif" alt="<?php echo $unit_names[$unit]; ?> Icon" class="w-12 h-12 rounded-md flex-shrink-0">
                    <div class="flex-grow">
                        <p class="font-bold text-white"><?php echo $unit_names[$unit]; ?></p>
                        <p class="text-xs text-yellow-400 font-semibold"><?php echo $unit_descriptions[$unit]; ?></p>
                        <p class="text-xs">Cost: <?php echo number_format($discounted_cost); ?> Credits</p>
                        <p class="text-xs">Owned: <?php echo number_format($user_stats[$unit]); ?></p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <input type="number" name="<?php echo $unit; ?>" min="0" placeholder="0" data-cost="<?php echo $cost; ?>" class="unit-input-train bg-gray-900 border border-gray-600 rounded-md w-24 text-center p-1">
                        <button type="button" class="train-max-btn text-xs bg-cyan-800 hover:bg-cyan-700 text-white font-semibold py-1 px-2 rounded-md">Max</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="content-box rounded-lg p-4 text-center">
            <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-3 px-8 rounded-lg transition-colors">Train All Selected Units</button>
        </div>
    </form>
</div>
    <!-- DISBAND TAB -->
    <<!-- /template/includes/training/disband_tab.php -->
<div id="disband-tab-content" class="<?php if ($current_tab !== 'disband') echo 'hidden'; ?>">
    <form id="disband-form" action="/battle.php" method="POST" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="action" value="disband">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach($unit_costs as $unit => $cost): ?>
            <div class="content-box rounded-lg p-3">
                <div class="flex items-center space-x-3">
                    <img src="/assets/img/<?php echo strtolower($unit_names[$unit]); ?>.avif" alt="<?php echo $unit_names[$unit]; ?> Icon" class="w-12 h-12 rounded-md flex-shrink-0">
                    <div class="flex-grow">
                        <p class="font-bold text-white"><?php echo $unit_names[$unit]; ?></p>
                        <p class="text-xs text-yellow-400 font-semibold"><?php echo $unit_descriptions[$unit]; ?></p>
                        <p class="text-xs">Refund: There are no refunds for disbanding troops</p>
                        <p class="text-xs">Owned: <?php echo number_format($user_stats[$unit]); ?></p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <input type="number" name="<?php echo $unit; ?>" min="0" max="<?php echo $user_stats[$unit]; ?>" placeholder="0" data-cost="<?php echo $cost; ?>" class="unit-input-disband bg-gray-900 border border-gray-600 rounded-md w-24 text-center p-1">
                        <button type="button" class="disband-max-btn text-xs bg-red-800 hover:bg-red-700 text-white font-semibold py-1 px-2 rounded-md">Max</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="content-box rounded-lg p-4 text-center">
            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-8 rounded-lg transition-colors">Disband All Selected Units</button>
        </div>
    </form>
</div>

    <!-- RECOVERY TAB -->
    <!-- /template/includes/training/recovery_tab.php -->
<div id="recovery-tab-content" class="<?php if ($current_tab !== 'recovery') echo 'hidden'; ?>">
    <div class="content-box rounded-lg p-4 space-y-3">
        <div class="flex flex-wrap items-center gap-4 text-sm">
            <div class="px-3 py-1 rounded bg-gray-800">
                Ready now: <span class="font-semibold text-green-400"><?php echo number_format($recovery_ready_total); ?></span>
            </div>
            <div class="px-3 py-1 rounded bg-gray-800">
                Locked (30m): <span class="font-semibold text-amber-300" id="locked-total"><?php echo number_format($recovery_locked_total); ?></span>
            </div>
        </div>

        <?php if (!$has_recovery_schema): ?>
            <p class="text-sm text-gray-300">No recovery data found.</p>
        <?php else: ?>
            <?php if (empty($recovery_rows)): ?>
                <p class="text-sm text-gray-300">No pending conversions. You're clear to train.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-300 border-b border-gray-700">
                                <th class="py-2 pr-4">Batch</th>
                                <th class="py-2 pr-4">From</th>
                                <th class="py-2 pr-4">Quantity</th>
                                <th class="py-2 pr-4">Available (UTC)</th>
                                <th class="py-2 pr-4">Time Remaining</th>
                            </tr>
                        </thead>
                        <tbody id="recovery-rows">
                            <?php foreach ($recovery_rows as $r): 
                                $is_ready = ($r['sec_remaining'] <= 0);
                                $batch_label = '#' . (int)$r['id'];
                                $from = htmlspecialchars(ucfirst($r['unit_type']));
                                $qty  = (int)$r['quantity'];
                                $avail = htmlspecialchars($r['available_at']);
                                $sec  = (int)$r['sec_remaining'];
                            ?>
                            <tr class="border-b border-gray-800">
                                <td class="py-2 pr-4"><?php echo $batch_label; ?></td>
                                <td class="py-2 pr-4"><?php echo $from; ?></td>
                                <td class="py-2 pr-4 font-semibold text-white"><?php echo number_format($qty); ?></td>
                                <td class="py-2 pr-4"><?php echo $avail; ?></td>
                                <td class="py-2 pr-4">
                                    <span
                                        class="inline-block px-2 py-0.5 rounded <?php echo $is_ready ? 'bg-green-900 text-green-300' : 'bg-yellow-900 text-amber-300'; ?>"
                                        data-countdown="<?php echo $sec; ?>"
                                        data-qty="<?php echo $qty; ?>"
                                    >
                                        <?php echo $is_ready ? 'Ready' : '—'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-400 mt-2">Tip: This table updates live; when a batch hits 00:00 it will flip to <span class="text-green-300 font-semibold">Ready</span>.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
</main>

<!-- /templates/includes/training/helpers.php -->
<script>
// Simple client-side tab toggling (no other files touched)
(function(){
    const tabs = [
        {btn:'train-tab-btn',    panel:'train-tab-content',    key:'train'},
        {btn:'disband-tab-btn',  panel:'disband-tab-content',  key:'disband'},
        {btn:'recovery-tab-btn', panel:'recovery-tab-content', key:'recovery'}
    ];
    function activate(key){
        tabs.forEach(t=>{
            const b = document.getElementById(t.btn);
            const p = document.getElementById(t.panel);
            if(!b||!p) return;
            const active = (t.key===key);
            p.classList.toggle('hidden', !active);
            b.classList.toggle('bg-gray-700', active);
            b.classList.toggle('text-white', active);
            b.classList.toggle('font-semibold', active);
            b.classList.toggle('bg-gray-800', !active);
            b.classList.toggle('text-gray-400', !active);
        });
        // Update URL param (so reload preserves tab)
        const u = new URL(window.location.href);
        u.searchParams.set('tab', key);
        window.history.replaceState({}, '', u.toString());
    }
    tabs.forEach(t=>{
        const b = document.getElementById(t.btn);
        if(b) b.addEventListener('click', ()=>activate(t.key));
    });
})();

// Live countdown for recovery rows
(function(){
    const nodes = Array.from(document.querySelectorAll('[data-countdown]'));
    if(nodes.length===0) return;
    const lockedTotalEl = document.getElementById('locked-total');

    function fmt(sec){
        if (sec <= 0) return "00:00";
        const h = Math.floor(sec/3600);
        const m = Math.floor((sec%3600)/60);
        const s = sec%60;
        return (h>0?String(h).padStart(2,'0')+':':'')+String(m).padStart(2,'0')+':'+String(s).padStart(2,'0');
    }

    function tick(){
        let lockedTotal = 0;
        nodes.forEach(el=>{
            let sec = parseInt(el.getAttribute('data-countdown'),10);
            const qty = parseInt(el.getAttribute('data-qty'),10) || 0;
            if (isNaN(sec)) return;

            if (sec <= 0){
                el.textContent = 'Ready';
                el.classList.remove('bg-yellow-900','text-amber-300');
                el.classList.add('bg-green-900','text-green-300');
                return;
            }
            sec -= 1;
            el.textContent = fmt(sec);
            el.setAttribute('data-countdown', sec);
            if (sec > 0) lockedTotal += qty;
        });
        if (lockedTotalEl){
            lockedTotalEl.textContent = lockedTotal.toLocaleString();
        }
    }
    tick();
    setInterval(tick, 1000);
})();
</script>

 </div>
        </div>
    </div>
    <footer class="bg-gray-900 bg-opacity-80 mt-16">
        <div class="container mx-auto px-6 py-8">
            <div class="flex flex-col items-center sm:flex-row sm:justify-between">
                <p class="text-sm text-gray-500">&copy; <?php echo date("Y"); ?> Cerberusrf Productions. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <script src="/assets/js/main.js" defer></script>
</body>
</html>?>