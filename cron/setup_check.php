<?php

// cron/setup_check.php

if (php_sapi_name() !== 'cli') {
    die('Access Denied: CLI only.');
}

require __DIR__ . '/../vendor/autoload.php';

$phpBin = PHP_BINARY; // Detects the PHP executable currently running this script
$projectRoot = realpath(__DIR__ . '/../');
$npcScript = $projectRoot . '/cron/process_npcs.php';
$turnScript = $projectRoot . '/cron/process_turn.php';
$npcLog = $projectRoot . '/logs/npc_actions.log';
$turnLog = $projectRoot . '/logs/cron.log';

echo "\n" . str_repeat("=", 50) . "\n";
echo "   STARLIGHT DOMINION CRON SETUP HELPER\n";
echo str_repeat("=", 50) . "\n\n";

echo "Detected Environment:\n";
echo " - PHP Binary:  {$phpBin}\n";
echo " - Project Dir: {$projectRoot}\n\n";

echo "To automate the NPC Agents (Void Syndicate), run:\n";
echo "  crontab -e\n\n";
echo "And add the following lines to the bottom of the file:\n\n";

// Generate the lines
$npcCron = "*/5 * * * * {$phpBin} {$npcScript} >> {$npcLog} 2>&1";
$turnCron = "*/5 * * * * {$phpBin} {$turnScript} >> {$turnLog} 2>&1";

echo "# Starlight Dominion - NPC AI (Every 5 Minutes)\n";
echo "{$npcCron}\n\n";

echo "# Starlight Dominion - Game Turn (Every 5 Minutes - Optional/If not already set)\n";
echo "{$turnCron}\n\n";

echo str_repeat("=", 50) . "\n";
echo "After saving, the NPCs will begin operating autonomously.\n";