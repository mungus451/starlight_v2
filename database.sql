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

-- Creates a new table for storing the results of all battles
CREATE TABLE `battle_reports` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `attacker_id` INT UNSIGNED NOT NULL,
    `defender_id` INT UNSIGNED NOT NULL,
    `attacker_outcome` ENUM('win', 'loss') NOT NULL,
    `battle_data` JSON NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    
    -- Foreign key to the user who launched the attack
    CONSTRAINT `fk_battle_attacker`
        FOREIGN KEY (`attacker_id`)
        REFERENCES `users`(`id`)
        ON DELETE CASCADE,
        
    -- Foreign key to the user who was targeted
    CONSTRAINT `fk_battle_defender`
        FOREIGN KEY (`defender_id`)
        REFERENCES `users`(`id`)
        ON DELETE CASCADE,
        
    -- Index to help players load their battle history
    INDEX `idx_attacker_reports` (`attacker_id`, `created_at` DESC),
    INDEX `idx_defender_reports` (`defender_id`, `created_at` DESC)
    
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

-- This converts the battle_reports table to InnoDB.
-- InnoDB supports transactions, which will prevent "phantom" reports
-- from being created when an attack fails mid-transaction.
ALTER TABLE `battle_reports` ENGINE=InnoDB;

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