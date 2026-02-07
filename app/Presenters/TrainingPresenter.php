<?php

namespace App\Presenters;

class TrainingPresenter
{
    public function present(array $data): array
    {
        // --- Page Configuration & Tab Management ---
        $page_title = 'Training & Fleet Management';
        $active_page = 'battle.php';
        $current_tab = $_GET['tab'] ?? 'train';
        if (!in_array($current_tab, ['train'])) {
            $current_tab = 'train';
        }

        // --- Data Adaptation Layer ---
        $resources = $data['resources'];
        $units = $data['units'];
        
        $user_stats = [
            'credits' => $resources->credits,
            'untrained_citizens' => $resources->untrained_citizens,
            'level' => $resources->level ?? 1,
            'experience' => $resources->experience ?? 0,
            'attack_turns' => $resources->attack_turns ?? 0,
            'last_updated' => $resources->last_updated ?? 'now',
            'gemstones' => $resources->gemstones ?? 0
        ];

        foreach ($units as $key => $unit) {
            $user_stats[$key] = $resources->{$key} ?? 0;
        }

        // Set defaults for data that might not be in this context
        $charisma_discount = 1.0;
        $recovery_ready_total = 0;
        $recovery_locked_total = 0;
        $has_recovery_schema = false;
        $recovery_rows = [];
        
        // Data for advisor panel
        $user_xp = $user_stats['experience'];
        $user_level = $user_stats['level'];
        
        // Data for navigation panel
        $_nav_credits = $user_stats['credits'];
        $_nav_gemstones = $user_stats['gemstones'];

        // Prepare for the header/nav include, which has its own logic
        // This is an existing issue, the presenter just ensures the vars are there
        global $link;
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

        return [
            'page_title' => $page_title,
            'active_page' => $active_page,
            'current_tab' => $current_tab,
            'user_stats' => $user_stats,
            'charisma_discount' => $charisma_discount,
            'recovery_ready_total' => $recovery_ready_total,
            'recovery_locked_total' => $recovery_locked_total,
            'has_recovery_schema' => $has_recovery_schema,
            'recovery_rows' => $recovery_rows,
            'user_xp' => $user_xp,
            'user_level' => $user_level,
            '_nav_credits' => $_nav_credits,
            '_nav_gemstones' => $_nav_gemstones,
            'units' => $units, // Pass the original enriched units array through
            'resources' => $resources, // Pass the original resources object through
            'csrf_token' => $data['csrf_token']
        ];
    }
}
