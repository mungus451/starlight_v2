-- Migration: Add Race Support
-- Description: Adds races table, links users to races, and adds race-exclusive resources
-- Date: 2025-12-15

-- Create races table with 5 races
CREATE TABLE `races` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `exclusive_resource` VARCHAR(50) NOT NULL,
    `lore` TEXT NOT NULL,
    `uses` TEXT NOT NULL,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_race_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert the 5 races
INSERT INTO `races` (`id`, `name`, `exclusive_resource`, `lore`, `uses`) VALUES
(1, 'Aridan Nomads', 'Whisperium Spice', 'Harvested from their desert world, used for psychic navigation and psionic tech.', 'Tier 4+ warp route plotting, advanced medical enhancers'),
(2, 'Luminarch Order', 'Aurorium Crystals', 'Cultivated from radiant crystal groves attuned to cosmic energy.', 'Advanced energy weapons, hyperdrive cores, phase shields'),
(3, 'Vorax Brood', 'Xenoplasm Bio-Gel', 'A living bio-organic secretion used in biotech fusion.', 'Self-healing armor, bio-weapon engineering, cybernetic upgrades'),
(4, 'Synthien Collective', 'Zerulium Cores', 'Exotic quantum matter refined from black hole reactors.', 'Quantum AI cores, teleportation drives, sentient tech construct'),
(5, 'The Synthera', 'Voidsteel Alloy', 'Forged in the void between dimensions, a material that exists partially outside normal space-time.', 'Dimensional cloaking, reality anchors, phase-shift weaponry');

-- Add race_id column to users table
ALTER TABLE `users`
    ADD COLUMN `race_id` INT UNSIGNED NULL DEFAULT NULL AFTER `character_name`,
    ADD CONSTRAINT `fk_user_race` 
        FOREIGN KEY (`race_id`) 
        REFERENCES `races`(`id`)
        ON DELETE SET NULL
        ON UPDATE NO ACTION;

-- Add race-exclusive resources to user_resources table
ALTER TABLE `user_resources`
    ADD COLUMN `whisperium_spice` DECIMAL(19,4) NOT NULL DEFAULT 0.0000 AFTER `gemstones`,
    ADD COLUMN `aurorium_crystals` DECIMAL(19,4) NOT NULL DEFAULT 0.0000 AFTER `whisperium_spice`,
    ADD COLUMN `xenoplasm_biogel` DECIMAL(19,4) NOT NULL DEFAULT 0.0000 AFTER `aurorium_crystals`,
    ADD COLUMN `zerulium_cores` DECIMAL(19,4) NOT NULL DEFAULT 0.0000 AFTER `xenoplasm_biogel`,
    ADD COLUMN `voidsteel_alloy` DECIMAL(19,4) NOT NULL DEFAULT 0.0000 AFTER `zerulium_cores`;
