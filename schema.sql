/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-12.0.2-MariaDB, for osx10.19 (x86_64)
--
-- Host: localhost    Database: starlightDB
-- ------------------------------------------------------
-- Server version 12.0.2-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `alliance_applications`
--

DROP TABLE IF EXISTS `alliance_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `alliance_applications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `alliance_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_alliance` (`user_id`,`alliance_id`),
  KEY `fk_app_alliance` (`alliance_id`),
  CONSTRAINT `fk_app_alliance` FOREIGN KEY (`alliance_id`) REFERENCES `alliances` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_app_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `alliance_bank_logs`
--

DROP TABLE IF EXISTS `alliance_bank_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `alliance_bank_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alliance_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL COMMENT 'User involved (donator, taxee)',
  `log_type` varchar(50) NOT NULL COMMENT 'e.g., ''donation'', ''battle_tax'', ''interest''',
  `amount` bigint(20) NOT NULL COMMENT 'Positive (income) or negative (expense)',
  `message` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_alliance_logs` (`alliance_id`,`created_at` DESC),
  KEY `fk_bank_log_user` (`user_id`),
  CONSTRAINT `fk_bank_log_alliance` FOREIGN KEY (`alliance_id`) REFERENCES `alliances` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bank_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `alliance_forum_posts`
--

DROP TABLE IF EXISTS `alliance_forum_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `alliance_forum_posts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `topic_id` int(10) unsigned NOT NULL,
  `alliance_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL COMMENT 'Author of the post',
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_alliance_forum_posts` (`topic_id`,`created_at`),
  KEY `fk_afp_alliance` (`alliance_id`),
  KEY `fk_afp_user` (`user_id`),
  CONSTRAINT `fk_afp_alliance` FOREIGN KEY (`alliance_id`) REFERENCES `alliances` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_afp_topic` FOREIGN KEY (`topic_id`) REFERENCES `alliance_forum_topics` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_afp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `alliance_forum_topics`
--

DROP TABLE IF EXISTS `alliance_forum_topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `alliance_forum_topics` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alliance_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL COMMENT 'Author/Creator of the topic',
  `title` varchar(255) NOT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_reply_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_reply_user_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_alliance_forum_topics` (`alliance_id`,`last_reply_at` DESC),
  KEY `fk_aft_user` (`user_id`),
  KEY `fk_aft_last_reply_user` (`last_reply_user_id`),
  CONSTRAINT `fk_aft_alliance` FOREIGN KEY (`alliance_id`) REFERENCES `alliances` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_aft_last_reply_user` FOREIGN KEY (`last_reply_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_aft_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `alliance_loans`
--

DROP TABLE IF EXISTS `alliance_loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `alliance_loans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alliance_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL COMMENT 'The user (borrower) requesting the loan',
  `amount_requested` bigint(20) unsigned NOT NULL,
  `amount_to_repay` bigint(20) unsigned NOT NULL DEFAULT 0,
  `status` enum('pending','active','paid','denied') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_alliance_loans_status` (`alliance_id`,`status`),
  KEY `idx_user_loans` (`user_id`,`status`),
  CONSTRAINT `fk_loan_alliance` FOREIGN KEY (`alliance_id`) REFERENCES `alliances` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_loan_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `alliance_roles`
--

DROP TABLE IF EXISTS `alliance_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `alliance_roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alliance_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 10,
  `can_edit_profile` tinyint(1) NOT NULL DEFAULT 0,
  `can_manage_applications` tinyint(1) NOT NULL DEFAULT 0,
  `can_invite_members` tinyint(1) NOT NULL DEFAULT 0,
  `can_kick_members` tinyint(1) NOT NULL DEFAULT 0,
  `can_manage_roles` tinyint(1) NOT NULL DEFAULT 0,
  `can_see_private_board` tinyint(1) NOT NULL DEFAULT 0,
  `can_manage_forum` tinyint(1) NOT NULL DEFAULT 0,
  `can_manage_bank` tinyint(1) NOT NULL DEFAULT 0,
  `can_manage_structures` tinyint(1) NOT NULL DEFAULT 0,
  `can_manage_diplomacy` tinyint(1) NOT NULL DEFAULT 0,
  `can_declare_war` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_role_alliance` (`alliance_id`),
  CONSTRAINT `fk_role_alliance` FOREIGN KEY (`alliance_id`) REFERENCES `alliances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `alliance_structures`
--

DROP TABLE IF EXISTS `alliance_structures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `alliance_structures` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alliance_id` int(10) unsigned NOT NULL,
  `structure_key` varchar(50) NOT NULL,
  `level` int(10) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_alliance_structure` (`alliance_id`,`structure_key`),
  KEY `fk_as_structure_def` (`structure_key`),
  CONSTRAINT `fk_as_alliance` FOREIGN KEY (`alliance_id`) REFERENCES `alliances` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_as_structure_def` FOREIGN KEY (`structure_key`) REFERENCES `alliance_structures_definitions` (`structure_key`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `alliance_structures_definitions`
--

DROP TABLE IF EXISTS `alliance_structures_definitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `alliance_structures_definitions` (
  `structure_key` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `base_cost` bigint(20) unsigned NOT NULL,
  `cost_multiplier` decimal(5,2) NOT NULL DEFAULT 1.50,
  `bonus_text` varchar(255) NOT NULL,
  `bonuses_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`bonuses_json`)),
  PRIMARY KEY (`structure_key`),
  CONSTRAINT `chk_bonuses_json` CHECK (json_valid(`bonuses_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `alliances`
--

DROP TABLE IF EXISTS `alliances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `alliances` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `tag` varchar(5) NOT NULL,
  `description` text DEFAULT NULL,
  `profile_picture_url` varchar(255) DEFAULT NULL,
  `is_joinable` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=Application, 1=Open',
  `leader_id` int(10) unsigned NOT NULL,
  `net_worth` bigint(20) NOT NULL DEFAULT 0,
  `bank_credits` bigint(20) unsigned NOT NULL DEFAULT 0,
  `last_compound_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`),
  UNIQUE KEY `uk_tag` (`tag`),
  KEY `fk_alliance_leader` (`leader_id`),
  CONSTRAINT `fk_alliance_leader` FOREIGN KEY (`leader_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `battle_reports`
--

DROP TABLE IF EXISTS `battle_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `battle_reports` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `attacker_id` int(10) unsigned NOT NULL,
  `defender_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `attack_type` enum('plunder','conquer') NOT NULL,
  `attack_result` enum('victory','defeat','stalemate') NOT NULL,
  `soldiers_sent` int(11) NOT NULL,
  `attacker_soldiers_lost` int(11) NOT NULL DEFAULT 0,
  `defender_guards_lost` int(11) NOT NULL DEFAULT 0,
  `credits_plundered` bigint(20) NOT NULL DEFAULT 0,
  `experience_gained` int(11) NOT NULL DEFAULT 0,
  `war_prestige_gained` int(11) NOT NULL DEFAULT 0,
  `net_worth_stolen` bigint(20) NOT NULL DEFAULT 0,
  `attacker_offense_power` int(11) NOT NULL,
  `defender_defense_power` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_attacker_created` (`attacker_id`,`created_at` DESC),
  KEY `idx_defender_created` (`defender_id`,`created_at` DESC),
  CONSTRAINT `fk_battle_attacker` FOREIGN KEY (`attacker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_battle_defender` FOREIGN KEY (`defender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rivalries`
--

DROP TABLE IF EXISTS `rivalries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rivalries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alliance_a_id` int(10) unsigned NOT NULL COMMENT 'The alliance with the lower ID',
  `alliance_b_id` int(10) unsigned NOT NULL COMMENT 'The alliance with the higher ID',
  `heat_level` int(11) NOT NULL DEFAULT 1,
  `last_attack_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_alliance_pair` (`alliance_a_id`,`alliance_b_id`),
  KEY `fk_rivalry_alliance_b` (`alliance_b_id`),
  CONSTRAINT `fk_rivalry_alliance_a` FOREIGN KEY (`alliance_a_id`) REFERENCES `alliances` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rivalry_alliance_b` FOREIGN KEY (`alliance_b_id`) REFERENCES `alliances` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_alliance_order` CHECK (`alliance_a_id` < `alliance_b_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spy_reports`
--

DROP TABLE IF EXISTS `spy_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `spy_reports` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `attacker_id` int(10) unsigned NOT NULL,
  `defender_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `operation_result` enum('success','failure') NOT NULL,
  `spies_sent` int(11) NOT NULL,
  `spies_lost_attacker` int(11) NOT NULL DEFAULT 0,
  `sentries_lost_defender` int(11) NOT NULL DEFAULT 0,
  `credits_seen` bigint(20) DEFAULT NULL,
  `gemstones_seen` bigint(20) DEFAULT NULL,
  `workers_seen` int(11) DEFAULT NULL,
  `soldiers_seen` int(11) DEFAULT NULL,
  `guards_seen` int(11) DEFAULT NULL,
  `spies_seen` int(11) DEFAULT NULL,
  `sentries_seen` int(11) DEFAULT NULL,
  `fortification_level_seen` int(11) DEFAULT NULL,
  `offense_upgrade_level_seen` int(11) DEFAULT NULL,
  `defense_upgrade_level_seen` int(11) DEFAULT NULL,
  `spy_upgrade_level_seen` int(11) DEFAULT NULL,
  `economy_upgrade_level_seen` int(11) DEFAULT NULL,
  `population_level_seen` int(11) DEFAULT NULL,
  `armory_level_seen` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_attacker_created` (`attacker_id`,`created_at` DESC),
  KEY `fk_report_defender` (`defender_id`),
  CONSTRAINT `fk_report_attacker` FOREIGN KEY (`attacker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_report_defender` FOREIGN KEY (`defender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `treaties`
--

DROP TABLE IF EXISTS `treaties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `treaties` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alliance1_id` int(10) unsigned NOT NULL COMMENT 'Proposing alliance',
  `alliance2_id` int(10) unsigned NOT NULL COMMENT 'Target alliance',
  `treaty_type` enum('peace','non_aggression','mutual_defense') NOT NULL,
  `status` enum('proposed','active','expired','broken','declined') NOT NULL DEFAULT 'proposed',
  `terms` text DEFAULT NULL,
  `expiration_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_alliance1_treaties` (`alliance1_id`,`status`),
  KEY `idx_alliance2_treaties` (`alliance2_id`,`status`),
  CONSTRAINT `fk_treaty_alliance1` FOREIGN KEY (`alliance1_id`) REFERENCES `alliances` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_treaty_alliance2` FOREIGN KEY (`alliance2_id`) REFERENCES `alliances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_armory_inventory`
--

DROP TABLE IF EXISTS `user_armory_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_armory_inventory` (
  `user_id` int(10) unsigned NOT NULL,
  `item_key` varchar(50) NOT NULL COMMENT 'e.g., ''pulse_rifle''',
  `quantity` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`,`item_key`),
  CONSTRAINT `fk_armory_inv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_resources`
--

DROP TABLE IF EXISTS `user_resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_resources` (
  `user_id` int(10) unsigned NOT NULL,
  `credits` bigint(20) NOT NULL DEFAULT 10000000,
  `banked_credits` bigint(20) unsigned NOT NULL DEFAULT 0,
  `gemstones` bigint(20) unsigned NOT NULL DEFAULT 0,
  `untrained_citizens` int(11) NOT NULL DEFAULT 250,
  `workers` int(11) NOT NULL DEFAULT 0,
  `soldiers` int(11) NOT NULL DEFAULT 0,
  `guards` int(11) NOT NULL DEFAULT 0,
  `spies` int(11) NOT NULL DEFAULT 0,
  `sentries` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_resources_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_security`
--

DROP TABLE IF EXISTS `user_security`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_security` (
  `user_id` int(10) unsigned NOT NULL,
  `question_1` varchar(255) DEFAULT NULL,
  `answer_1_hash` varchar(255) DEFAULT NULL,
  `question_2` varchar(255) DEFAULT NULL,
  `answer_2_hash` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_security_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_stats`
--

DROP TABLE IF EXISTS `user_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_stats` (
  `user_id` int(10) unsigned NOT NULL,
  `level` int(11) NOT NULL DEFAULT 1,
  `experience` bigint(20) NOT NULL DEFAULT 0,
  `net_worth` bigint(20) NOT NULL DEFAULT 500,
  `war_prestige` int(11) NOT NULL DEFAULT 0,
  `energy` int(11) NOT NULL DEFAULT 10,
  `attack_turns` int(11) NOT NULL DEFAULT 10,
  `level_up_points` int(11) NOT NULL DEFAULT 1,
  `strength_points` int(11) NOT NULL DEFAULT 0,
  `constitution_points` int(11) NOT NULL DEFAULT 0,
  `wealth_points` int(11) NOT NULL DEFAULT 0,
  `dexterity_points` int(11) NOT NULL DEFAULT 0,
  `charisma_points` int(11) NOT NULL DEFAULT 0,
  `deposit_charges` int(11) NOT NULL DEFAULT 4,
  `last_deposit_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_stats_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_structures`
--

DROP TABLE IF EXISTS `user_structures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_structures` (
  `user_id` int(10) unsigned NOT NULL,
  `fortification_level` int(11) NOT NULL DEFAULT 0,
  `offense_upgrade_level` int(11) NOT NULL DEFAULT 0,
  `defense_upgrade_level` int(11) NOT NULL DEFAULT 0,
  `spy_upgrade_level` int(11) NOT NULL DEFAULT 0,
  `economy_upgrade_level` int(11) NOT NULL DEFAULT 0,
  `population_level` int(11) NOT NULL DEFAULT 0,
  `armory_level` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_structures_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_unit_loadouts`
--

DROP TABLE IF EXISTS `user_unit_loadouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_unit_loadouts` (
  `user_id` int(10) unsigned NOT NULL,
  `unit_key` varchar(25) NOT NULL COMMENT 'e.g., ''soldier''',
  `category_key` varchar(50) NOT NULL COMMENT 'e.g., ''main_weapon''',
  `item_key` varchar(50) NOT NULL COMMENT 'e.g., ''pulse_rifle''',
  PRIMARY KEY (`user_id`,`unit_key`,`category_key`),
  CONSTRAINT `fk_armory_loadout_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(191) NOT NULL,
  `character_name` varchar(50) NOT NULL,
  `bio` text DEFAULT NULL,
  `profile_picture_url` varchar(255) DEFAULT NULL,
  `phone_number` varchar(25) DEFAULT NULL,
  `alliance_id` int(10) unsigned DEFAULT NULL,
  `alliance_role_id` int(10) unsigned DEFAULT NULL,
  `is_npc` tinyint(1) NOT NULL DEFAULT 0,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`),
  UNIQUE KEY `uk_character_name` (`character_name`),
  KEY `fk_user_alliance` (`alliance_id`),
  KEY `fk_user_alliance_role` (`alliance_role_id`),
  KEY `idx_is_npc` (`is_npc`),
  CONSTRAINT `fk_user_alliance` FOREIGN KEY (`alliance_id`) REFERENCES `alliances` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_user_alliance_role` FOREIGN KEY (`alliance_role_id`) REFERENCES `alliance_roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `war_battle_logs`
--

DROP TABLE IF EXISTS `war_battle_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `war_battle_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `war_id` int(10) unsigned NOT NULL,
  `battle_report_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `alliance_id` int(10) unsigned NOT NULL,
  `prestige_gained` int(11) NOT NULL DEFAULT 0,
  `units_killed` int(11) NOT NULL DEFAULT 0,
  `credits_plundered` bigint(20) NOT NULL DEFAULT 0,
  `structure_damage` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_battle_report` (`battle_report_id`),
  KEY `idx_war_logs` (`war_id`,`alliance_id`),
  KEY `fk_wbl_user` (`user_id`),
  KEY `fk_wbl_alliance` (`alliance_id`),
  CONSTRAINT `fk_wbl_alliance` FOREIGN KEY (`alliance_id`) REFERENCES `alliances` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wbl_battle_report` FOREIGN KEY (`battle_report_id`) REFERENCES `battle_reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wbl_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wbl_war` FOREIGN KEY (`war_id`) REFERENCES `wars` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `war_history`
--

DROP TABLE IF EXISTS `war_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `war_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `war_id` int(10) unsigned NOT NULL,
  `declarer_name` varchar(100) NOT NULL,
  `defender_name` varchar(100) NOT NULL,
  `casus_belli_text` text DEFAULT NULL,
  `goal_text` varchar(255) NOT NULL,
  `outcome` varchar(255) NOT NULL,
  `mvp_metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`mvp_metadata_json`)),
  `final_stats_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`final_stats_json`)),
  `start_time` timestamp NOT NULL,
  `end_time` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_war_id` (`war_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wars`
--

DROP TABLE IF EXISTS `wars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wars` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `war_type` enum('skirmish','war') NOT NULL DEFAULT 'war',
  `declarer_alliance_id` int(10) unsigned NOT NULL,
  `declared_against_alliance_id` int(10) unsigned NOT NULL,
  `casus_belli` text DEFAULT NULL,
  `status` enum('active','concluded') NOT NULL DEFAULT 'active',
  `goal_key` varchar(100) NOT NULL COMMENT 'e.g., ''credits_plundered'', ''units_killed''',
  `goal_threshold` bigint(20) unsigned NOT NULL,
  `declarer_score` bigint(20) unsigned NOT NULL DEFAULT 0,
  `defender_score` bigint(20) unsigned NOT NULL DEFAULT 0,
  `winner_alliance_id` int(10) unsigned DEFAULT NULL,
  `start_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_active_wars` (`status`,`start_time`),
  KEY `idx_declarer_wars` (`declarer_alliance_id`,`status`),
  KEY `idx_defender_wars` (`declared_against_alliance_id`,`status`),
  KEY `fk_war_winner` (`winner_alliance_id`),
  CONSTRAINT `fk_war_declarer` FOREIGN KEY (`declarer_alliance_id`) REFERENCES `alliances` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_war_defender` FOREIGN KEY (`declared_against_alliance_id`) REFERENCES `alliances` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_war_winner` FOREIGN KEY (`winner_alliance_id`) REFERENCES `alliances` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2025-11-16 10:40:54
/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-12.0.2-MariaDB, for osx10.19 (x86_64)
--
-- Host: localhost    Database: starlightDB
-- ------------------------------------------------------
-- Server version 12.0.2-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Dumping data for table `alliance_structures_definitions`
--

LOCK TABLES `alliance_structures_definitions` WRITE;
/*!40000 ALTER TABLE `alliance_structures_definitions` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `alliance_structures_definitions` VALUES
('citadel_shield','Citadel Shield Array','Grants a global defense bonus to all members of the alliance.',100000000,1.80,'+10% Defense','[{\"type\":\"defense_bonus_percent\",\"value\":0.1}]'),
('command_nexus','Command Nexus','Provides a bonus to all credit income for all members.',150000000,1.70,'+5% Income/Turn','[{\"type\":\"income_bonus_percent\",\"value\":0.05}]'),
('galactic_research_hub','Galactic Research Hub','Increases all resource production for alliance members.',120000000,1.75,'+10% Resources','[{\"type\":\"resource_bonus_percent\",\"value\":0.1}]'),
('orbital_training_grounds','Orbital Training Grounds','Grants a global offense bonus to all members of the alliance.',100000000,1.80,'+5% Offense','[{\"type\":\"offense_bonus_percent\",\"value\":0.05}]'),
('population_habitat','Population Habitat','Provides a flat boost to citizen growth per turn for all members.',50000000,1.60,'+5 Citizens/Turn','[{\"type\":\"citizen_growth_flat\",\"value\":5}]'),
('warlords_throne','Warlord\'s Throne','A monument to your alliance\'s power. Greatly boosts all other structure bonuses.',500000000,2.50,'+15% to all bonuses','[{\"type\":\"all_bonus_multiplier\",\"value\":0.15}]');
/*!40000 ALTER TABLE `alliance_structures_definitions` ENABLE KEYS */;
UNLOCK TABLES;
commit;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2025-11-16 10:41:06