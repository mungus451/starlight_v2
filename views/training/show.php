<?php
// --- Mapped variables from the controller ---
/** @var \App\Models\Entities\UserResource $resources */
/** @var array $units */
/** @var string $csrf_token */

// --- Page Configuration & Tab Management ---
$page_title = 'Training & Fleet Management';
$active_page = 'battle.php'; // Assuming this is correct for navigation highlighting
$current_tab = $_GET['tab'] ?? 'train';
if (!in_array($current_tab, ['train', 'disband', 'recovery'])) {
    $current_tab = 'train';
}

// --- Data Adaptation Layer ---

// The template expects a $user_stats array. Let's build it from the $resources object.
$user_stats = [
    'credits' => $resources->credits,
    'untrained_citizens' => $resources->untrained_citizens,
    'level' => $resources->level ?? 1, // Add defaults for advisor panel
    'experience' => $resources->experience ?? 0,
    'attack_turns' => $resources->attack_turns ?? 0,
    'last_updated' => $resources->last_updated ?? 'now',
    'gemstones' => $resources->gemstones ?? 0
];

// The template expects separated unit data arrays. The Disband tab needs this.
$unit_costs = [];

// Also populate the owned units into the $user_stats array for the template
foreach ($units as $key => $unit) {
    $unit_costs[$key] = $unit['credits'];
    $user_stats[$key] = $resources->{$key} ?? 0;
}

// Set defaults for data not provided by the controller for this view
$charisma_discount = 1.0; // Default to no discount
$recovery_ready_total = 0;
$recovery_locked_total = 0;
$has_recovery_schema = false; // So the template shows "no data"
$recovery_rows = [];

// The template expects these for the advisor panel's XP bar.
$user_xp = $user_stats['experience'];
$user_level = $user_stats['level'];
// This is used for the header nav, I'll mock it with the available stats
$_nav_credits = $user_stats['credits'];
$_nav_gemstones = $user_stats['gemstones'];

// NOTE: The header navigation in the template does a lot of its own DB lookups.
// This is not ideal but for this refactoring, we will let it stand.
global $link; // The navigation template part requires this.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$user_id = (int)($_SESSION['id'] ?? 0);
if ($user_id > 0 && (!isset($link) || !($link instanceof \mysqli) || !@mysqli_ping($link))) {
    require_once dirname(__DIR__, 2) . '/config/config.php';
    $link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($link) {
        mysqli_query($link, "SET time_zone = '+00:00'");
    }
}
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
    <link rel="icon" type="image/avif" href="/assets/img/favicon.avif">
    <link rel="icon" type="image/png" href="/assets/img/favicon.png" sizes="32x32">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="text-gray-400 antialiased">
    <div class="min-h-screen bg-cover bg-center bg-fixed" style="background-image: url('/assets/img/backgroundAlt.avif');">
        <div class="container mx-auto p-4 md:p-8">
 
        <?php
        // Embedded navigation from example - to be refactored into a layout later
        include dirname(__DIR__) . '/layouts/navigation.php';
        ?>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 p-4">



            <main class="lg:col-span-4 space-y-4">

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
                        <button id="train-tab-btn" class="tab-btn <?php echo ($current_tab === 'train') ? 'bg-gray-700 text-white font-semibold' : 'bg-gray-800 hover:bg-gray-700 text-gray-400'; ?> py-3 px-6 rounded-t-lg text-base transition-colors">Train Units</button>
                        <button id="disband-tab-btn" class="tab-btn <?php echo ($current_tab === 'disband') ? 'bg-gray-700 text-white font-semibold' : 'bg-gray-800 hover:bg-gray-700 text-gray-400'; ?> py-3 px-6 rounded-t-lg text-base transition-colors">Disband Units</button>
                        <button id="recovery-tab-btn" class="tab-btn <?php echo ($current_tab === 'recovery') ? 'bg-gray-700 text-white font-semibold' : 'bg-gray-800 hover:bg-gray-700 text-gray-400'; ?> py-3 px-6 rounded-t-lg text-base transition-colors">Recovery Queue</button>
                    </nav>
                </div>

                <!-- TRAIN TAB -->
                <div id="train-tab-content" class="<?php if ($current_tab !== 'train') echo 'hidden'; ?>">
                    <form id="train-form" action="/training/train" method="POST" class="space-y-4" data-charisma-discount="<?php echo $charisma_discount; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="train">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach($units as $key => $unit): 
                                $discounted_cost = floor($unit['credits'] * $charisma_discount);
                                $ownedCount = $user_stats[$key] ?? 0;
                            ?>
                            <div class="content-box rounded-lg p-3">
                                <div class="flex items-center space-x-3">
                                    <img src="/assets/img/<?php echo strtolower($unit['name']); ?>.avif" alt="<?php echo $unit['name']; ?> Icon" class="w-12 h-12 rounded-md flex-shrink-0">
                                    <div class="flex-grow">
                                        <p class="font-bold text-white"><?php echo $unit['name']; ?></p>
                                        <p class="text-xs text-yellow-400 font-semibold"><?php echo $unit['desc']; ?></p>
                                        <p class="text-xs">Cost: <?php echo number_format($discounted_cost); ?> Credits</p>
                                        <p class="text-xs">Owned: <?php echo number_format($ownedCount); ?></p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <input type="number" name="units[<?php echo $key; ?>]" min="0" placeholder="0" data-cost="<?php echo $unit['credits']; ?>" class="unit-input-train bg-gray-900 border border-gray-600 rounded-md w-24 text-center p-1">
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
                <div id="disband-tab-content" class="<?php if ($current_tab !== 'disband') echo 'hidden'; ?>">
                    <form id="disband-form" action="/training/disband" method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="disband">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach($units as $key => $unit): ?>
                            <div class="content-box rounded-lg p-3">
                                <div class="flex items-center space-x-3">
                                    <img src="/assets/img/<?php echo strtolower($unit['name']); ?>.avif" alt="<?php echo $unit['name']; ?> Icon" class="w-12 h-12 rounded-md flex-shrink-0">
                                    <div class="flex-grow">
                                        <p class="font-bold text-white"><?php echo $unit['name']; ?></p>
                                        <p class="text-xs">Refund: None</p>
                                        <p class="text-xs">Owned: <?php echo number_format($user_stats[$key] ?? 0); ?></p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <input type="number" name="units[<?php echo $key; ?>]" min="0" max="<?php echo $user_stats[$key] ?? 0; ?>" placeholder="0" class="unit-input-disband bg-gray-900 border border-gray-600 rounded-md w-24 text-center p-1">
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
                            <p class="text-sm text-gray-300">No recovery data found for your account.</p>
                        <?php else: ?>
                            <?php if (empty($recovery_rows)): ?>
                                <p class="text-sm text-gray-300">No units are currently in recovery.</p>
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
                                            <?php foreach ($recovery_rows as $r): ?>
                                            <tr class="border-b border-gray-800">
                                                <td class="py-2 pr-4">#<?php echo (int)$r['id']; ?></td>
                                                <td class="py-2 pr-4"><?php echo htmlspecialchars(ucfirst($r['unit_type'])); ?></td>
                                                <td class="py-2 pr-4 font-semibold text-white"><?php echo number_format((int)$r['quantity']); ?></td>
                                                <td class="py-2 pr-4"><?php echo htmlspecialchars($r['available_at']); ?></td>
                                                <td class="py-2 pr-4">
                                                    <span class="inline-block px-2 py-0.5 rounded <?php echo ((int)$r['sec_remaining'] <= 0) ? 'bg-green-900 text-green-300' : 'bg-yellow-900 text-amber-300'; ?>" data-countdown="<?php echo (int)$r['sec_remaining']; ?>" data-qty="<?php echo (int)$r['quantity']; ?>">
                                                        <?php echo ((int)$r['sec_remaining'] <= 0) ? 'Ready' : '—'; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <footer class="bg-gray-900 bg-opacity-80 mt-16">
        <div class="container mx-auto px-6 py-8">
            <p class="text-sm text-gray-500 text-center">&copy; <?php echo date("Y"); ?> Cerberusrf Productions. All rights reserved.</p>
        </div>
    </footer>

    <script src="/js/training.js"></script>
    <script>
    // Simple client-side tab toggling
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
            const u = new URL(window.location.href);
            u.searchParams.set('tab', key);
            window.history.replaceState({}, '', u.toString());
        }
        tabs.forEach(t=>{
            const b = document.getElementById(t.btn);
            if(b) b.addEventListener('click', ()=>activate(t.key));
        });
    })();
    </script>
</body>
</html>