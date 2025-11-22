#Running Log of additions to recreate the Starlightdominion DB in mariaDB

-- Phase 1: Create the new 'users' table
-- This table only stores identity information.
-- All other data (resources, stats) will be in separate tables.

CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(191) NOT NULL,
    `character_name` VARCHAR(50) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_email` (`email`),
    UNIQUE KEY `uk_character_name` (`character_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for all of a user's resources
-- Replaces the many columns from the old 'users' table

CREATE TABLE `user_resources` (
    `user_id` INT UNSIGNED NOT NULL,
    `credits` BIGINT NOT NULL DEFAULT 10000000,
    `banked_credits` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `gemstones` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `untrained_citizens` INT NOT NULL DEFAULT 250,
    `workers` INT NOT NULL DEFAULT 0,
    `soldiers` INT NOT NULL DEFAULT 0,
    `guards` INT NOT NULL DEFAULT 0,
    `spies` INT NOT NULL DEFAULT 0,
    `sentries` INT NOT NULL DEFAULT 0,
    
    PRIMARY KEY (`user_id`),
    CONSTRAINT `fk_resources_user`
        FOREIGN KEY (`user_id`) 
        REFERENCES `users`(`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for a user's stats
-- Replaces more columns from the old 'users' table

CREATE TABLE `user_stats` (
    `user_id` INT UNSIGNED NOT NULL,
    `level` INT NOT NULL DEFAULT 1,
    `experience` BIGINT NOT NULL DEFAULT 0,
    `net_worth` BIGINT NOT NULL DEFAULT 500,
    `war_prestige` INT NOT NULL DEFAULT 0,
    `energy` INT NOT NULL DEFAULT 10,
    `attack_turns` INT NOT NULL DEFAULT 10,
    `level_up_points` INT NOT NULL DEFAULT 1,
    `strength_points` INT NOT NULL DEFAULT 0,
    `constitution_points` INT NOT NULL DEFAULT 0,
    `wealth_points` INT NOT NULL DEFAULT 0,
    `dexterity_points` INT NOT NULL DEFAULT 0,
    `charisma_points` INT NOT NULL DEFAULT 0,
    
    PRIMARY KEY (`user_id`),
    CONSTRAINT `fk_stats_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users`(`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for a user's structures
-- Replaces more columns from the old 'users' table

CREATE TABLE `user_structures` (
    `user_id` INT UNSIGNED NOT NULL,
    `fortification_level` INT NOT NULL DEFAULT 0,
    `offense_upgrade_level` INT NOT NULL DEFAULT 0,
    `defense_upgrade_level` INT NOT NULL DEFAULT 0,
    `spy_upgrade_level` INT NOT NULL DEFAULT 0,
    `economy_upgrade_level` INT NOT NULL DEFAULT 0,
    `population_level` INT NOT NULL DEFAULT 0,
    `armory_level` INT NOT NULL DEFAULT 0,
    
    PRIMARY KEY (`user_id`),
    CONSTRAINT `fk_structures_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users`(`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adds new columns to the 'users' table for profile settings
ALTER TABLE `users`
    ADD COLUMN `bio` TEXT NULL DEFAULT NULL AFTER `character_name`,
    ADD COLUMN `profile_picture_url` VARCHAR(255) NULL DEFAULT NULL AFTER `bio`,
    ADD COLUMN `phone_number` VARCHAR(25) NULL DEFAULT NULL AFTER `profile_picture_url`;

-- Creates a new table for storing user security questions
CREATE TABLE `user_security` (
    `user_id` INT UNSIGNED NOT NULL,
    `question_1` VARCHAR(255) NULL DEFAULT NULL,
    `answer_1_hash` VARCHAR(255) NULL DEFAULT NULL,
    `question_2` VARCHAR(255) NULL DEFAULT NULL,
    `answer_2_hash` VARCHAR(255) NULL DEFAULT NULL,
    
    PRIMARY KEY (`user_id`),
    CONSTRAINT `fk_security_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users`(`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Creates a new table to log all espionage operations
CREATE TABLE `spy_reports` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `attacker_id` INT UNSIGNED NOT NULL,
    `defender_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `operation_result` ENUM('success', 'failure') NOT NULL,
    `spies_sent` INT NOT NULL,
    `spies_lost_attacker` INT NOT NULL DEFAULT 0,
    `sentries_lost_defender` INT NOT NULL DEFAULT 0,
    
    -- Intel Payload (all nullable)
    `credits_seen` BIGINT NULL DEFAULT NULL,
    `gemstones_seen` BIGINT NULL DEFAULT NULL,
    `workers_seen` INT NULL DEFAULT NULL,
    `soldiers_seen` INT NULL DEFAULT NULL,
    `guards_seen` INT NULL DEFAULT NULL,
    `spies_seen` INT NULL DEFAULT NULL,
    `sentries_seen` INT NULL DEFAULT NULL,
    
    `fortification_level_seen` INT NULL DEFAULT NULL,
    `offense_upgrade_level_seen` INT NULL DEFAULT NULL,
    `defense_upgrade_level_seen` INT NULL DEFAULT NULL,
    `spy_upgrade_level_seen` INT NULL DEFAULT NULL,
    `economy_upgrade_level_seen` INT NULL DEFAULT NULL,
    `population_level_seen` INT NULL DEFAULT NULL,
    `armory_level_seen` INT NULL DEFAULT NULL,
    
    PRIMARY KEY (`id`),
    KEY `idx_attacker_created` (`attacker_id`, `created_at` DESC),
    CONSTRAINT `fk_report_attacker`
        FOREIGN KEY (`attacker_id`) 
        REFERENCES `users`(`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_report_defender`
        FOREIGN KEY (`defender_id`) 
        REFERENCES `users`(`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Creates a new table to log all player-vs-player battles
CREATE TABLE `battle_reports` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `attacker_id` INT UNSIGNED NOT NULL,
    `defender_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `attack_type` ENUM('plunder', 'conquer') NOT NULL,
    `attack_result` ENUM('victory', 'defeat', 'stalemate') NOT NULL,
    
    -- Forces
    `soldiers_sent` INT NOT NULL,
    `attacker_soldiers_lost` INT NOT NULL DEFAULT 0,
    `defender_guards_lost` INT NOT NULL DEFAULT 0,
    
    -- Spoils
    `credits_plundered` BIGINT NOT NULL DEFAULT 0,
    `experience_gained` INT NOT NULL DEFAULT 0,
    `war_prestige_gained` INT NOT NULL DEFAULT 0,
    `net_worth_stolen` BIGINT NOT NULL DEFAULT 0,
    
    -- Battle Snapshot (for the report)
    `attacker_offense_power` INT NOT NULL,
    `defender_defense_power` INT NOT NULL,
    
    PRIMARY KEY (`id`),
    KEY `idx_attacker_created` (`attacker_id`, `created_at` DESC),
    KEY `idx_defender_created` (`defender_id`, `created_at` DESC),
    CONSTRAINT `fk_battle_attacker`
        FOREIGN KEY (`attacker_id`) 
        REFERENCES `users`(`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_battle_defender`
        FOREIGN KEY (`defender_id`) 
        REFERENCES `users`(`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Creates the new 'alliances' table
CREATE TABLE `alliances` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `tag` VARCHAR(5) NOT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `profile_picture_url` VARCHAR(255) NULL DEFAULT NULL,
    `leader_id` INT UNSIGNED NOT NULL,
    `net_worth` BIGINT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_name` (`name`),
    UNIQUE KEY `uk_tag` (`tag`),
    CONSTRAINT `fk_alliance_leader`
        FOREIGN KEY (`leader_id`) 
        REFERENCES `users`(`id`)
        ON DELETE RESTRICT -- Don't allow deleting a user who is a leader
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adds new columns to the 'users' table for alliance membership
ALTER TABLE `users`
    ADD COLUMN `alliance_id` INT UNSIGNED NULL DEFAULT NULL AFTER `phone_number`,
    ADD COLUMN `alliance_role` VARCHAR(50) NULL DEFAULT NULL AFTER `alliance_id`,
    ADD CONSTRAINT `fk_user_alliance`
        FOREIGN KEY (`alliance_id`) 
        REFERENCES `alliances`(`id`)
        ON DELETE SET NULL; -- If an alliance is deleted, set the user's alliance_id to NULL

-- Creates a new table to store pending alliance applications
CREATE TABLE `alliance_applications` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `alliance_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    -- A user can only apply to a specific alliance once
    UNIQUE KEY `uk_user_alliance` (`user_id`, `alliance_id`), 
    
    CONSTRAINT `fk_app_user`
        FOREIGN KEY (`user_id`) 
        REFERENCES `users`(`id`)
        ON DELETE CASCADE, -- If the user is deleted, their application is deleted
    
    CONSTRAINT `fk_app_alliance`
        FOREIGN KEY (`alliance_id`) 
        REFERENCES `alliances`(`id`)
        ON DELETE CASCADE -- If the alliance is deleted, all its applications are deleted
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Creates the new table for dynamic, permission-based alliance roles
CREATE TABLE `alliance_roles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `alliance_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 10,
    
    -- Permission Flags
    `can_edit_profile` TINYINT(1) NOT NULL DEFAULT 0,
    `can_manage_applications` TINYINT(1) NOT NULL DEFAULT 0,
    `can_invite_members` TINYINT(1) NOT NULL DEFAULT 0,
    `can_kick_members` TINYINT(1) NOT NULL DEFAULT 0,
    `can_manage_roles` TINYINT(1) NOT NULL DEFAULT 0, -- Can create/edit/delete roles
    `can_see_private_board` TINYINT(1) NOT NULL DEFAULT 0,
    `can_manage_forum` TINYINT(1) NOT NULL DEFAULT 0,
    `can_manage_bank` TINYINT(1) NOT NULL DEFAULT 0,
    `can_manage_structures` TINYINT(1) NOT NULL DEFAULT 0,
    
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_role_alliance`
        FOREIGN KEY (`alliance_id`) 
        REFERENCES `alliances`(`id`)
        ON DELETE CASCADE -- If the alliance is deleted, all its roles are deleted
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Drops the old simple role column
ALTER TABLE `users`
    DROP FOREIGN KEY `fk_user_alliance`,
    DROP COLUMN `alliance_role`;

-- Adds the new role ID column
ALTER TABLE `users`
    ADD COLUMN `alliance_role_id` INT UNSIGNED NULL DEFAULT NULL AFTER `alliance_id`,
    
    -- Re-add the alliance foreign key (which we had to drop)
    ADD CONSTRAINT `fk_user_alliance`
        FOREIGN KEY (`alliance_id`) 
        REFERENCES `alliances`(`id`)
        ON DELETE SET NULL,
        
    -- Add the new role foreign key
    ADD CONSTRAINT `fk_user_alliance_role`
        FOREIGN KEY (`alliance_role_id`) 
        REFERENCES `alliance_roles`(`id`)
        ON DELETE SET NULL; -- If a role is deleted, the user becomes "role-less"

        --
-- Table structure for table `user_armory_inventory`
-- Tracks the quantity of each item a user has manufactured.
--
CREATE TABLE `user_armory_inventory` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `item_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g., ''pulse_rifle''',
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`,`item_key`),
  CONSTRAINT `fk_armory_inv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `user_unit_loadouts`
-- Tracks which item is currently equipped for a unit''s category slot.
--
CREATE TABLE `user_unit_loadouts` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `unit_key` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g., ''soldier''',
  `category_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g., ''main_weapon''',
  `item_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g., ''pulse_rifle''',
  PRIMARY KEY (`user_id`,`unit_key`,`category_key`),
  CONSTRAINT `fk_armory_loadout_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bank Updates

ALTER TABLE `user_stats`
    ADD COLUMN `deposit_charges` INT NOT NULL DEFAULT 4 AFTER `charisma_points`,
    ADD COLUMN `last_deposit_at` TIMESTAMP NULL DEFAULT NULL AFTER `deposit_charges`;

--
-- --------------------------------------------------------
--
-- PHASE 12: ALLIANCE TREASURY (CORE)
--
-- Add new treasury columns to the 'alliances' table
ALTER TABLE `alliances`
    ADD COLUMN `bank_credits` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `net_worth`,
    ADD COLUMN `is_joinable` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=Application, 1=Open' AFTER `profile_picture_url`,
    ADD COLUMN `last_compound_at` TIMESTAMP NULL DEFAULT NULL AFTER `bank_credits`;

--
-- Table structure for table `alliance_bank_logs`
-- Tracks all transactions (donations, taxes, structure costs)
--
CREATE TABLE `alliance_bank_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `alliance_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'User involved (donator, taxee)',
    `log_type` VARCHAR(50) NOT NULL COMMENT 'e.g., ''donation'', ''battle_tax'', ''interest''',
    `amount` BIGINT NOT NULL COMMENT 'Positive (income) or negative (expense)',
    `message` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    
    KEY `idx_alliance_logs` (`alliance_id`, `created_at` DESC),
    
    CONSTRAINT `fk_bank_log_alliance`
        FOREIGN KEY (`alliance_id`) 
        REFERENCES `alliances`(`id`)
        ON DELETE CASCADE,
        
    CONSTRAINT `fk_bank_log_user`
        FOREIGN KEY (`user_id`) 
        REFERENCES `users`(`id`)
        ON DELETE SET NULL -- Keep log even if user is deleted

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- --------------------------------------------------------
--
-- PHASE 13: ALLIANCE STRUCTURES
--
-- Table structure for table `alliance_structures_definitions`
-- Defines all purchasable alliance-wide upgrades
--
CREATE TABLE `alliance_structures_definitions` (
    `structure_key` VARCHAR(50) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `base_cost` BIGINT UNSIGNED NOT NULL,
    `cost_multiplier` DECIMAL(5,2) NOT NULL DEFAULT 1.5,
    `bonus_text` VARCHAR(255) NOT NULL,
    `bonuses_json` JSON NOT NULL,
    
    PRIMARY KEY (`structure_key`),
    CONSTRAINT `chk_bonuses_json` CHECK (JSON_VALID(`bonuses_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `alliance_structures`
-- Tracks the level of structures an alliance currently owns
--
CREATE TABLE `alliance_structures` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `alliance_id` INT UNSIGNED NOT NULL,
    `structure_key` VARCHAR(50) NOT NULL,
    `level` INT UNSIGNED NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_alliance_structure` (`alliance_id`, `structure_key`),
    
    CONSTRAINT `fk_as_alliance`
        FOREIGN KEY (`alliance_id`) 
        REFERENCES `alliances`(`id`)
        ON DELETE CASCADE,
        
    CONSTRAINT `fk_as_structure_def`
        FOREIGN KEY (`structure_key`) 
        REFERENCES `alliance_structures_definitions`(`structure_key`)
        ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- --------------------------------------------------------
--
-- PHASE 14: ALLIANCE FORUM
--
-- Table structure for table `alliance_forum_topics`
-- Defines a single "thread" or "topic" in an alliance forum
--
CREATE TABLE `alliance_forum_topics` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `alliance_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL COMMENT 'Author/Creator of the topic',
    `title` VARCHAR(255) NOT NULL,
    `is_locked` TINYINT(1) NOT NULL DEFAULT 0,
    `is_pinned` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_reply_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_reply_user_id` INT UNSIGNED NULL DEFAULT NULL,
    
    PRIMARY KEY (`id`),
    KEY `idx_alliance_forum_topics` (`alliance_id`, `last_reply_at` DESC),
    
    CONSTRAINT `fk_aft_alliance`
        FOREIGN KEY (`alliance_id`) 
        REFERENCES `alliances`(`id`)
        ON DELETE CASCADE,
        
    CONSTRAINT `fk_aft_user`
        FOREIGN KEY (`user_id`) 
        REFERENCES `users`(`id`)
        ON DELETE CASCADE,
        
    CONSTRAINT `fk_aft_last_reply_user`
        FOREIGN KEY (`last_reply_user_id`) 
        REFERENCES `users`(`id`)
        ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `alliance_forum_posts`
-- Defines a single post (reply) within an alliance forum topic
--
CREATE TABLE `alliance_forum_posts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `topic_id` INT UNSIGNED NOT NULL,
    `alliance_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL COMMENT 'Author of the post',
    `content` TEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    KEY `idx_alliance_forum_posts` (`topic_id`, `created_at` ASC),
    
    CONSTRAINT `fk_afp_topic`
        FOREIGN KEY (`topic_id`) 
        REFERENCES `alliance_forum_topics`(`id`)
        ON DELETE CASCADE,
        
    CONSTRAINT `fk_afp_alliance`
        FOREIGN KEY (`alliance_id`) 
        REFERENCES `alliances`(`id`)
        ON DELETE CASCADE,
        
    CONSTRAINT `fk_afp_user`
        FOREIGN KEY (`user_id`) 
        REFERENCES `users`(`id`)
        ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- --------------------------------------------------------
--
-- PHASE 15: ALLIANCE LOANS
--
-- Table structure for table `alliance_loans`
-- Tracks member loan requests from the alliance bank
--
CREATE TABLE `alliance_loans` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `alliance_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL COMMENT 'The user (borrower) requesting the loan',
    `amount_requested` BIGINT UNSIGNED NOT NULL,
    `amount_to_repay` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `status` ENUM('pending', 'active', 'paid', 'denied') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    KEY `idx_alliance_loans_status` (`alliance_id`, `status`),
    KEY `idx_user_loans` (`user_id`, `status`),
    
    CONSTRAINT `fk_loan_alliance`
        FOREIGN KEY (`alliance_id`) 
        REFERENCES `alliances`(`id`)
        ON DELETE CASCADE,
        
    CONSTRAINT `fk_loan_user`
        FOREIGN KEY (`user_id`) 
        REFERENCES `users`(`id`)
        ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- --------------------------------------------------------
--
-- PHASE 16a: DIPLOMACY
--
-- Add new permission column to 'alliance_roles'
ALTER TABLE `alliance_roles`
    ADD COLUMN `can_manage_diplomacy` TINYINT(1) NOT NULL DEFAULT 0 AFTER `can_manage_structures`;

--
-- Table structure for table `treaties`
-- Tracks bilateral pacts between alliances
--
CREATE TABLE `treaties` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `alliance1_id` INT UNSIGNED NOT NULL COMMENT 'Proposing alliance',
    `alliance2_id` INT UNSIGNED NOT NULL COMMENT 'Target alliance',
    `treaty_type` ENUM('peace', 'non_aggression', 'mutual_defense') NOT NULL,
    `status` ENUM('proposed', 'active', 'expired', 'broken', 'declined') NOT NULL DEFAULT 'proposed',
    `terms` TEXT NULL DEFAULT NULL,
    `expiration_date` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    KEY `idx_alliance1_treaties` (`alliance1_id`, `status`),
    KEY `idx_alliance2_treaties` (`alliance2_id`, `status`),
    
    CONSTRAINT `fk_treaty_alliance1`
        FOREIGN KEY (`alliance1_id`) 
        REFERENCES `alliances`(`id`)
        ON DELETE CASCADE,
        
    CONSTRAINT `fk_treaty_alliance2`
        FOREIGN KEY (`alliance2_id`) 
        REFERENCES `alliances`(`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `rivalries`
-- Tracks friction between two alliances
--
CREATE TABLE `rivalries` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `alliance_a_id` INT UNSIGNED NOT NULL COMMENT 'The alliance with the lower ID',
    `alliance_b_id` INT UNSIGNED NOT NULL COMMENT 'The alliance with the higher ID',
    `heat_level` INT NOT NULL DEFAULT 1,
    `last_attack_date` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_alliance_pair` (`alliance_a_id`, `alliance_b_id`),
    
    CONSTRAINT `fk_rivalry_alliance_a`
        FOREIGN KEY (`alliance_a_id`) 
        REFERENCES `alliances`(`id`)
        ON DELETE CASCADE,
        
    CONSTRAINT `fk_rivalry_alliance_b`
        FOREIGN KEY (`alliance_b_id`) 
        REFERENCES `alliances`(`id`)
        ON DELETE CASCADE,

    CONSTRAINT `chk_alliance_order` CHECK (`alliance_a_id` < `alliance_b_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- --------------------------------------------------------
--
-- PHASE 16b: WARS
--
-- Add new permission column to 'alliance_roles' for declaring war
ALTER TABLE `alliance_roles`
    ADD COLUMN `can_declare_war` TINYINT(1) NOT NULL DEFAULT 0 AFTER `can_manage_diplomacy`;

--
-- Table structure for table `wars`
-- Tracks all active, pending, and concluded wars
--
CREATE TABLE `wars` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `war_type` ENUM('skirmish', 'war') NOT NULL DEFAULT 'war',
    `declarer_alliance_id` INT UNSIGNED NOT NULL,
    `declared_against_alliance_id` INT UNSIGNED NOT NULL,
    `casus_belli` TEXT NULL DEFAULT NULL,
    `status` ENUM('active', 'concluded') NOT NULL DEFAULT 'active',
    `goal_key` VARCHAR(100) NOT NULL COMMENT 'e.g., ''credits_plundered'', ''units_killed''',
    `goal_threshold` BIGINT UNSIGNED NOT NULL,
    `declarer_score` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `defender_score` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `winner_alliance_id` INT UNSIGNED NULL DEFAULT NULL,
    `start_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `end_time` TIMESTAMP NULL DEFAULT NULL,
    
    PRIMARY KEY (`id`),
    KEY `idx_active_wars` (`status`, `start_time`),
    KEY `idx_declarer_wars` (`declarer_alliance_id`, `status`),
    KEY `idx_defender_wars` (`declared_against_alliance_id`, `status`),
    
    CONSTRAINT `fk_war_declarer`
        FOREIGN KEY (`declarer_alliance_id`) 
        REFERENCES `alliances`(`id`)
        ON DELETE CASCADE,
        
    CONSTRAINT `fk_war_defender`
        FOREIGN KEY (`declared_against_alliance_id`) 
        REFERENCES `alliances`(`id`)
        ON DELETE CASCADE,
        
    CONSTRAINT `fk_war_winner`
        FOREIGN KEY (`winner_alliance_id`) 
        REFERENCES `alliances`(`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `war_battle_logs`
-- Links individual battle reports to a specific war
--
CREATE TABLE `war_battle_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `war_id` INT UNSIGNED NOT NULL,
    `battle_report_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `alliance_id` INT UNSIGNED NOT NULL,
    `prestige_gained` INT NOT NULL DEFAULT 0,
    `units_killed` INT NOT NULL DEFAULT 0,
    `credits_plundered` BIGINT NOT NULL DEFAULT 0,
    `structure_damage` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_battle_report` (`battle_report_id`),
    KEY `idx_war_logs` (`war_id`, `alliance_id`),
    
    CONSTRAINT `fk_wbl_war`
        FOREIGN KEY (`war_id`) 
        REFERENCES `wars`(`id`)
        ON DELETE CASCADE,
        
    CONSTRAINT `fk_wbl_battle_report`
        FOREIGN KEY (`battle_report_id`) 
        REFERENCES `battle_reports`(`id`)
        ON DELETE CASCADE,
        
    CONSTRAINT `fk_wbl_user`
        FOREIGN KEY (`user_id`) 
        REFERENCES `users`(`id`)
        ON DELETE CASCADE,
        
    CONSTRAINT `fk_wbl_alliance`
        FOREIGN KEY (`alliance_id`) 
        REFERENCES `alliances`(`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `war_history`
-- A snapshot of concluded wars for archival
--
CREATE TABLE `war_history` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `war_id` INT UNSIGNED NOT NULL,
    `declarer_name` VARCHAR(100) NOT NULL,
    `defender_name` VARCHAR(100) NOT NULL,
    `casus_belli_text` TEXT NULL DEFAULT NULL,
    `goal_text` VARCHAR(255) NOT NULL,
    `outcome` VARCHAR(255) NOT NULL,
    `mvp_metadata_json` JSON NULL DEFAULT NULL,
    `final_stats_json` JSON NULL DEFAULT NULL,
    `start_time` TIMESTAMP NOT NULL,
    `end_time` TIMESTAMP NOT NULL,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_war_id` (`war_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
            `id` VARCHAR(128) NOT NULL,
            `user_id` INT UNSIGNED NULL DEFAULT NULL,
            `payload` MEDIUMTEXT NOT NULL,
            `last_activity` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_last_activity` (`last_activity`),
            KEY `idx_user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rate_limits` (
            `client_hash` VARCHAR(64) NOT NULL,
            `route_uri` VARCHAR(191) NOT NULL,
            `request_count` INT UNSIGNED NOT NULL DEFAULT 1,
            `window_start` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`client_hash`, `route_uri`),
            KEY `idx_window` (`window_start`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT UNSIGNED NOT NULL,
            `type` ENUM('attack', 'spy', 'alliance', 'system') NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `message` TEXT NOT NULL,
            `link` VARCHAR(255) NULL DEFAULT NULL,
            `is_read` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            
            PRIMARY KEY (`id`),
            
            -- Foreign Key constraint to ensure data integrity
            CONSTRAINT `fk_notification_user`
                FOREIGN KEY (`user_id`) 
                REFERENCES `users`(`id`)
                ON DELETE CASCADE,

            -- Index for fetching unread counts quickly (The 'Red Badge' query)
            KEY `idx_user_read` (`user_id`, `is_read`),
            
            -- Index for paginating recent notifications history
            KEY `idx_user_created` (`user_id`, `created_at` DESC)
            
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;