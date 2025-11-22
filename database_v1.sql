-- StarlightDominion V1 Database Schema
-- This is the complete V1 schema with all tables
-- The database.sql file contains migrations to transform this to V2

-- Users table (denormalized structure in V1)
CREATE TABLE `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `character_name` VARCHAR(50) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `race` VARCHAR(50) NOT NULL,
    `class` VARCHAR(50) NOT NULL,
    `phone_number` VARCHAR(20) DEFAULT NULL,
    `phone_carrier` VARCHAR(50) DEFAULT NULL,
    `phone_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `avatar_path` VARCHAR(255) DEFAULT NULL,
    `biography` TEXT DEFAULT NULL,
    `level` INT(11) NOT NULL DEFAULT 1,
    `experience` INT(11) NOT NULL DEFAULT 0,
    `credits` BIGINT(20) NOT NULL DEFAULT 10000000,
    `banked_credits` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    `untrained_citizens` INT(11) NOT NULL DEFAULT 250,
    `workers` INT(11) NOT NULL DEFAULT 0,
    `soldiers` INT(11) NOT NULL DEFAULT 0,
    `guards` INT(11) NOT NULL DEFAULT 0,
    `sentries` INT(11) NOT NULL DEFAULT 0,
    `spies` INT(11) NOT NULL DEFAULT 0,
    `spy_offense` INT(11) NOT NULL DEFAULT 10,
    `sentry_defense` INT(11) NOT NULL DEFAULT 10,
    `net_worth` BIGINT(20) NOT NULL DEFAULT 500,
    `war_prestige` INT(11) NOT NULL DEFAULT 0,
    `credit_rating` VARCHAR(3) NOT NULL DEFAULT 'C',
    `energy` INT(11) NOT NULL DEFAULT 10,
    `attack_turns` INT(11) NOT NULL DEFAULT 10,
    `level_up_points` INT(11) NOT NULL DEFAULT 1,
    `strength_points` INT(11) NOT NULL DEFAULT 0,
    `constitution_points` INT(11) NOT NULL DEFAULT 0,
    `wealth_points` INT(11) NOT NULL DEFAULT 0,
    `dexterity_points` INT(11) NOT NULL DEFAULT 0,
    `charisma_points` INT(11) NOT NULL DEFAULT 0,
    `last_updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `vacation_until` DATETIME DEFAULT NULL,
    `deposits_today` INT(3) NOT NULL DEFAULT 0,
    `last_deposit_timestamp` TIMESTAMP NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_login_ip` VARCHAR(45) DEFAULT NULL,
    `last_login_at` TIMESTAMP NULL DEFAULT NULL,
    `previous_login_ip` VARCHAR(45) DEFAULT NULL,
    `previous_login_at` TIMESTAMP NULL DEFAULT NULL,
    `fortification_level` INT(11) NOT NULL DEFAULT 0,
    `fortification_hitpoints` BIGINT(20) NOT NULL DEFAULT 0,
    `offense_upgrade_level` INT(11) NOT NULL DEFAULT 0,
    `defense_upgrade_level` INT(11) NOT NULL DEFAULT 0,
    `spy_upgrade_level` INT(11) NOT NULL DEFAULT 0,
    `economy_upgrade_level` INT(11) NOT NULL DEFAULT 0,
    `population_level` INT(11) NOT NULL DEFAULT 0,
    `armory_level` INT(11) NOT NULL DEFAULT 0,
    `alliance_id` INT(11) DEFAULT NULL,
    `alliance_role_id` INT(11) DEFAULT NULL,
    `holo_knights` INT(11) NOT NULL DEFAULT 0,
    `warp_barons` INT(11) NOT NULL DEFAULT 0,
    `rage_cyborgs` INT(11) NOT NULL DEFAULT 0,
    `is_npc` TINYINT(1) NOT NULL DEFAULT 0,
    `last_seen_at` TIMESTAMP NULL DEFAULT NULL,
    `gemstones` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    `reroll_tokens` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `black_market_reputation` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`),
    UNIQUE KEY `character_name` (`character_name`),
    KEY `level` (`level`),
    KEY `net_worth` (`net_worth`),
    KEY `alliance_id` (`alliance_id`),
    KEY `alliance_role_id` (`alliance_role_id`),
    KEY `last_login_at` (`last_login_at`),
    KEY `last_seen_at` (`last_seen_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alliances
CREATE TABLE `alliances` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `tag` VARCHAR(5) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `avatar_path` VARCHAR(255) DEFAULT 'assets/img/default_alliance.png',
    `leader_id` INT(11) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `bank_credits` BIGINT(20) NOT NULL DEFAULT 0,
    `war_prestige` INT(11) NOT NULL DEFAULT 0,
    `is_joinable` TINYINT(1) NOT NULL DEFAULT 1,
    `last_compound_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    UNIQUE KEY `tag` (`tag`),
    KEY `leader_id` (`leader_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alliance Applications
CREATE TABLE `alliance_applications` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `alliance_id` INT(11) NOT NULL,
    `status` ENUM('pending','approved','denied') NOT NULL DEFAULT 'pending',
    `application_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `alliance_id` (`alliance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alliance Bank Logs
CREATE TABLE `alliance_bank_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `alliance_id` INT(11) NOT NULL,
    `user_id` INT(11) DEFAULT NULL,
    `type` ENUM('deposit','withdrawal','purchase','tax','transfer_fee','loan_given','loan_repaid','interest_yield') NOT NULL,
    `amount` BIGINT(20) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `comment` VARCHAR(255) DEFAULT NULL,
    `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alliance Forum Posts
CREATE TABLE `alliance_forum_posts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `alliance_id` INT(11) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `post_content` TEXT NOT NULL,
    `post_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_pinned` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `alliance_id` (`alliance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alliance Invitations
CREATE TABLE `alliance_invitations` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `alliance_id` INT(11) NOT NULL,
    `inviter_id` INT(11) NOT NULL,
    `invitee_id` INT(11) NOT NULL,
    `status` ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `invitee_id` (`invitee_id`),
    KEY `alliance_id` (`alliance_id`),
    KEY `inviter_id` (`inviter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alliance Loans
CREATE TABLE `alliance_loans` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `alliance_id` INT(11) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `amount_loaned` BIGINT(20) NOT NULL,
    `amount_to_repay` BIGINT(20) NOT NULL,
    `status` ENUM('pending','active','paid','denied') NOT NULL DEFAULT 'pending',
    `request_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `approval_date` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `alliance_id` (`alliance_id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alliance Roles
CREATE TABLE `alliance_roles` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `alliance_id` INT(11) NOT NULL,
    `name` VARCHAR(50) NOT NULL,
    `order` INT(11) NOT NULL,
    `is_deletable` TINYINT(1) NOT NULL DEFAULT 1,
    `can_edit_profile` TINYINT(1) NOT NULL DEFAULT 0,
    `can_approve_membership` TINYINT(1) NOT NULL DEFAULT 0,
    `can_kick_members` TINYINT(1) NOT NULL DEFAULT 0,
    `can_manage_roles` TINYINT(1) NOT NULL DEFAULT 0,
    `can_manage_structures` TINYINT(1) NOT NULL DEFAULT 0,
    `can_manage_treasury` TINYINT(1) NOT NULL DEFAULT 0,
    `can_invite_members` TINYINT(1) NOT NULL DEFAULT 0,
    `can_moderate_forum` TINYINT(1) NOT NULL DEFAULT 0,
    `can_sticky_threads` TINYINT(1) NOT NULL DEFAULT 0,
    `can_lock_threads` TINYINT(1) NOT NULL DEFAULT 0,
    `can_delete_posts` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `alliance_id` (`alliance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alliance Structures
CREATE TABLE `alliance_structures` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `alliance_id` INT(11) NOT NULL,
    `structure_key` VARCHAR(50) NOT NULL,
    `level` INT(11) NOT NULL DEFAULT 1,
    `purchase_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `alliance_id` (`alliance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alliance Structure Definitions
CREATE TABLE `alliance_structures_definitions` (
    `structure_key` VARCHAR(50) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `cost` BIGINT(20) NOT NULL,
    `bonus_text` VARCHAR(255) NOT NULL,
    `bonuses` LONGTEXT NOT NULL,
    PRIMARY KEY (`structure_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Armory Attrition Logs
CREATE TABLE `armory_attrition_logs` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `battle_log_id` INT(11) DEFAULT NULL,
    `category_key` VARCHAR(50) NOT NULL,
    `item_key` VARCHAR(50) NOT NULL,
    `qty_lost` INT(11) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `battle_log_id` (`battle_log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Auto Recruit Usage
CREATE TABLE `auto_recruit_usage` (
    `recruiter_id` INT(11) NOT NULL,
    `usage_date` DATE NOT NULL DEFAULT (CURDATE()),
    `daily_count` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
    `last_used_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`recruiter_id`, `usage_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Badges
CREATE TABLE `badges` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(80) NOT NULL,
    `icon_path` VARCHAR(255) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bank Transactions
CREATE TABLE `bank_transactions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `transaction_type` ENUM('deposit','withdraw') NOT NULL,
    `amount` BIGINT(20) UNSIGNED NOT NULL,
    `transaction_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Battle Logs
CREATE TABLE `battle_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `attacker_id` INT(11) NOT NULL,
    `defender_id` INT(11) NOT NULL,
    `attacker_name` VARCHAR(50) NOT NULL,
    `defender_name` VARCHAR(50) NOT NULL,
    `outcome` ENUM('victory','defeat') NOT NULL,
    `credits_stolen` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    `attack_turns_used` INT(11) NOT NULL,
    `attacker_damage` BIGINT(20) NOT NULL,
    `defender_damage` BIGINT(20) NOT NULL,
    `attacker_xp_gained` BIGINT(20) DEFAULT 0,
    `defender_xp_gained` BIGINT(20) DEFAULT 0,
    `guards_lost` BIGINT(20) DEFAULT 0,
    `structure_damage` BIGINT(20) DEFAULT 0,
    `attacker_soldiers_lost` BIGINT(20) DEFAULT 0,
    `battle_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `battle_date` DATE GENERATED ALWAYS AS (CAST(`battle_time` AS DATE)) STORED,
    PRIMARY KEY (`id`),
    KEY `attacker_id` (`attacker_id`),
    KEY `defender_id` (`defender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Black Market Conversion Logs
CREATE TABLE `black_market_conversion_logs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `direction` ENUM('credits_to_gems','gems_to_credits') NOT NULL,
    `credits_spent` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    `gemstones_spent` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    `gemstones_received` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    `credits_received` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    `house_fee_credits` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Black Market Cosmic Rolls
CREATE TABLE `black_market_cosmic_rolls` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `selected_symbol` VARCHAR(16) NOT NULL,
    `bet_gemstones` BIGINT(20) NOT NULL DEFAULT 0,
    `pot_gemstones` BIGINT(20) NOT NULL DEFAULT 0,
    `result` VARCHAR(16) NOT NULL,
    `reel1` VARCHAR(16) NOT NULL,
    `reel2` VARCHAR(16) NOT NULL,
    `reel3` VARCHAR(16) NOT NULL,
    `matches` TINYINT(3) UNSIGNED NOT NULL,
    `house_gems_delta` BIGINT(20) NOT NULL DEFAULT 0,
    `user_gems_before` BIGINT(20) NOT NULL DEFAULT 0,
    `user_gems_after` BIGINT(20) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `result` (`result`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Black Market House Totals
CREATE TABLE `black_market_house_totals` (
    `id` TINYINT(3) UNSIGNED NOT NULL,
    `credits_collected` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    `gemstones_collected` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    `gemstones_paid_out` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Black Market Roulette Logs
CREATE TABLE `black_market_roulette_logs` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(10) UNSIGNED NOT NULL,
    `bets_placed` LONGTEXT NOT NULL,
    `total_bet` BIGINT(20) NOT NULL,
    `winning_number` TINYINT(4) NOT NULL,
    `total_winnings` BIGINT(20) NOT NULL,
    `net_result` BIGINT(20) NOT NULL,
    `user_gemstones_after` BIGINT(20) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Daily Recruits
CREATE TABLE `daily_recruits` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `recruiter_id` INT(11) NOT NULL,
    `recruited_id` INT(11) NOT NULL,
    `recruit_count` INT(11) NOT NULL DEFAULT 1,
    `recruit_date` DATE NOT NULL,
    PRIMARY KEY (`id`),
    KEY `recruiter_id` (`recruiter_id`),
    KEY `recruited_id` (`recruited_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Dice Matches
CREATE TABLE `data_dice_matches` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `status` ENUM('active','won','lost') NOT NULL DEFAULT 'active',
    `player_dice_remaining` TINYINT(3) UNSIGNED NOT NULL DEFAULT 5,
    `ai_dice_remaining` TINYINT(3) UNSIGNED NOT NULL DEFAULT 5,
    `pot_gemstones` INT(10) UNSIGNED NOT NULL DEFAULT 50,
    `bet_gemstones` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `ai_name` VARCHAR(32) NOT NULL DEFAULT 'Cipher',
    `payout_done` TINYINT(1) NOT NULL DEFAULT 0,
    `started_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ended_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Dice Rounds
CREATE TABLE `data_dice_rounds` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `match_id` BIGINT(20) UNSIGNED NOT NULL,
    `user_id` INT(11) NOT NULL,
    `round_no` INT(10) UNSIGNED NOT NULL,
    `last_claim_by` ENUM('player','ai') DEFAULT NULL,
    `claim_qty` TINYINT(3) UNSIGNED DEFAULT NULL,
    `claim_face` TINYINT(3) UNSIGNED DEFAULT NULL,
    `trace_called_by` ENUM('player','ai') DEFAULT NULL,
    `trace_was_correct` TINYINT(1) DEFAULT NULL,
    `loser` ENUM('player','ai') DEFAULT NULL,
    `counted_qty` TINYINT(3) UNSIGNED DEFAULT NULL,
    `player_roll` LONGTEXT NOT NULL,
    `ai_roll` LONGTEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `match_id` (`match_id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Economic Log
CREATE TABLE `economic_log` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `event_type` VARCHAR(50) NOT NULL,
    `amount` BIGINT(20) NOT NULL DEFAULT 0,
    `burned_amount` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    `on_hand_before` BIGINT(20) UNSIGNED NOT NULL,
    `on_hand_after` BIGINT(20) UNSIGNED NOT NULL,
    `banked_before` BIGINT(20) UNSIGNED NOT NULL,
    `banked_after` BIGINT(20) UNSIGNED NOT NULL,
    `gems_before` BIGINT(20) UNSIGNED NOT NULL,
    `gems_after` BIGINT(20) UNSIGNED NOT NULL,
    `reference_id` INT(10) UNSIGNED DEFAULT NULL,
    `metadata` LONGTEXT DEFAULT NULL,
    `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Forum Posts
CREATE TABLE `forum_posts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `thread_id` INT(11) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `content` TEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `thread_id` (`thread_id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Forum Threads
CREATE TABLE `forum_threads` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `alliance_id` INT(11) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_post_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_stickied` TINYINT(1) NOT NULL DEFAULT 0,
    `is_locked` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `alliance_id` (`alliance_id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password Resets
CREATE TABLE `password_resets` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `token` (`token`),
    KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Penalized Units
CREATE TABLE `penalized_units` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `unit_type` VARCHAR(255) NOT NULL,
    `untrained_count` INT(11) NOT NULL,
    `penalty_end_time` DATETIME NOT NULL,
    `penalty_ends_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rivalries
CREATE TABLE `rivalries` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `alliance1_id` INT(11) NOT NULL,
    `alliance2_id` INT(11) NOT NULL,
    `heat_level` INT(11) NOT NULL DEFAULT 0,
    `last_attack_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `a_min` INT(11) GENERATED ALWAYS AS (LEAST(`alliance1_id`,`alliance2_id`)) STORED,
    `a_max` INT(11) GENERATED ALWAYS AS (GREATEST(`alliance1_id`,`alliance2_id`)) STORED,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `alliance1_id` (`alliance1_id`),
    KEY `alliance2_id` (`alliance2_id`),
    KEY `a_min` (`a_min`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Spy Logs
CREATE TABLE `spy_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `attacker_id` INT(11) NOT NULL,
    `defender_id` INT(11) NOT NULL,
    `mission_type` VARCHAR(50) NOT NULL,
    `outcome` ENUM('success','failure') NOT NULL,
    `intel_gathered` TEXT DEFAULT NULL,
    `units_killed` INT(11) NOT NULL DEFAULT 0,
    `structure_damage` INT(11) NOT NULL DEFAULT 0,
    `attacker_spy_power` INT(11) NOT NULL,
    `defender_sentry_power` INT(11) NOT NULL,
    `attacker_xp_gained` INT(11) NOT NULL DEFAULT 0,
    `defender_xp_gained` INT(11) NOT NULL DEFAULT 0,
    `mission_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `attacker_id` (`attacker_id`),
    KEY `defender_id` (`defender_id`),
    KEY `mission_type` (`mission_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Spy Total Sabotage Usage
CREATE TABLE `spy_total_sabotage_usage` (
    `user_id` INT(11) NOT NULL,
    `window_start` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `uses` INT(11) NOT NULL DEFAULT 0,
    `last_used_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Treaties
CREATE TABLE `treaties` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `alliance1_id` INT(11) NOT NULL,
    `alliance2_id` INT(11) NOT NULL,
    `treaty_type` ENUM('peace','non_aggression','mutual_defense') NOT NULL,
    `proposer_id` INT(11) NOT NULL,
    `status` ENUM('proposed','active','expired','broken','declined') NOT NULL DEFAULT 'proposed',
    `terms` TEXT DEFAULT NULL,
    `expiration_date` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `alliance1_id` (`alliance1_id`),
    KEY `alliance2_id` (`alliance2_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Untrained Units
CREATE TABLE `untrained_units` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `unit_type` VARCHAR(255) NOT NULL,
    `quantity` INT(11) NOT NULL,
    `penalty_ends` INT(11) NOT NULL,
    `available_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `available_at` (`available_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Unverified Users
CREATE TABLE `unverified_users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `character_name` VARCHAR(50) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `race` VARCHAR(50) NOT NULL,
    `class` VARCHAR(50) NOT NULL,
    `verification_code` VARCHAR(6) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Armory
CREATE TABLE `user_armory` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `item_key` VARCHAR(50) NOT NULL,
    `quantity` INT(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Badges
CREATE TABLE `user_badges` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `badge_id` INT(10) UNSIGNED NOT NULL,
    `earned_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `badge_id` (`badge_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Remember Tokens
CREATE TABLE `user_remember_tokens` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `selector` CHAR(12) NOT NULL,
    `token_hash` CHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `user_agent_hash` CHAR(64) DEFAULT NULL,
    `ip_prefix` VARBINARY(8) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `selector` (`selector`),
    KEY `user_id` (`user_id`),
    KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Security Questions
CREATE TABLE `user_security_questions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `question_id` INT(11) NOT NULL,
    `answer_hash` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Stat Snapshots
CREATE TABLE `user_stat_snapshots` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `offense_power` INT(11) NOT NULL,
    `defense_rating` INT(11) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Structure Health
CREATE TABLE `user_structure_health` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `structure_key` VARCHAR(50) NOT NULL,
    `health_pct` TINYINT(3) UNSIGNED NOT NULL DEFAULT 100,
    `locked` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Vaults
CREATE TABLE `user_vaults` (
    `user_id` INT(11) NOT NULL,
    `active_vaults` INT(10) UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- View: Black Market House Net
CREATE OR REPLACE VIEW `v_black_market_house_net` AS
SELECT 
    `id`,
    `credits_collected`,
    `gemstones_collected`,
    `gemstones_paid_out`,
    (`gemstones_collected` - `gemstones_paid_out`) AS `gemstones_net`,
    `updated_at`
FROM `black_market_house_totals`;

-- War Battle Logs
CREATE TABLE `war_battle_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `war_id` INT(11) NOT NULL,
    `battle_log_id` INT(11) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `alliance_id` INT(11) NOT NULL,
    `prestige_gained` INT(11) NOT NULL DEFAULT 0,
    `units_killed` INT(11) NOT NULL DEFAULT 0,
    `credits_plundered` BIGINT(20) NOT NULL DEFAULT 0,
    `structure_damage` BIGINT(20) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `war_id` (`war_id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- War History
CREATE TABLE `war_history` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `war_id` INT(11) NOT NULL,
    `declarer_alliance_name` VARCHAR(50) NOT NULL,
    `declared_against_alliance_name` VARCHAR(50) NOT NULL,
    `declarer_user_name` VARCHAR(50) DEFAULT NULL,
    `declared_against_user_name` VARCHAR(50) DEFAULT NULL,
    `start_date` TIMESTAMP NOT NULL,
    `end_date` TIMESTAMP NOT NULL,
    `outcome` VARCHAR(255) NOT NULL,
    `casus_belli_text` VARCHAR(255) NOT NULL,
    `goal_text` VARCHAR(255) NOT NULL,
    `mvp_user_id` INT(11) DEFAULT NULL,
    `mvp_category` VARCHAR(50) DEFAULT NULL,
    `mvp_value` BIGINT(20) DEFAULT NULL,
    `mvp_character_name` VARCHAR(50) DEFAULT NULL,
    `final_stats` TEXT DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `war_id` (`war_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wars
CREATE TABLE `wars` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `scope` ENUM('alliance','player') NOT NULL DEFAULT 'alliance',
    `name` VARCHAR(100) NOT NULL,
    `war_type` ENUM('skirmish','war') NOT NULL DEFAULT 'skirmish',
    `declarer_alliance_id` INT(11) DEFAULT NULL,
    `declarer_user_id` INT(11) DEFAULT NULL,
    `declared_against_alliance_id` INT(11) DEFAULT NULL,
    `declared_against_user_id` INT(11) DEFAULT NULL,
    `casus_belli_key` VARCHAR(255) DEFAULT NULL,
    `casus_belli_custom` TEXT DEFAULT NULL,
    `custom_badge_name` VARCHAR(100) DEFAULT NULL,
    `custom_badge_description` VARCHAR(255) DEFAULT NULL,
    `custom_badge_icon_path` VARCHAR(255) DEFAULT NULL,
    `start_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `end_date` DATETIME DEFAULT NULL,
    `status` VARCHAR(255) NOT NULL DEFAULT 'active',
    `defense_bonus_pct` TINYINT(3) UNSIGNED NOT NULL DEFAULT 3,
    `outcome` VARCHAR(255) DEFAULT NULL,
    `goal_key` VARCHAR(255) DEFAULT NULL,
    `goal_custom_label` VARCHAR(100) DEFAULT NULL,
    `goal_metric` VARCHAR(50) NOT NULL,
    `goal_threshold` INT(11) NOT NULL,
    `goal_credits_plundered` BIGINT(20) NOT NULL DEFAULT 0,
    `goal_units_killed` INT(11) NOT NULL DEFAULT 0,
    `goal_structure_damage` BIGINT(20) NOT NULL DEFAULT 0,
    `goal_prestige_change` INT(11) NOT NULL DEFAULT 0,
    `goal_progress_declarer` INT(11) NOT NULL DEFAULT 0,
    `goal_progress_declared_against` INT(11) NOT NULL DEFAULT 0,
    `score_declarer` BIGINT(20) NOT NULL DEFAULT 0,
    `score_defender` BIGINT(20) NOT NULL DEFAULT 0,
    `winner` ENUM('declarer','defender','draw') DEFAULT NULL,
    `calculated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `scope` (`scope`),
    KEY `status` (`status`),
    KEY `declarer_alliance_id` (`declarer_alliance_id`),
    KEY `declarer_user_id` (`declarer_user_id`),
    KEY `declared_against_alliance_id` (`declared_against_alliance_id`),
    KEY `declared_against_user_id` (`declared_against_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
