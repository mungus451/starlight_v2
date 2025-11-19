# Migrations & Utilities

This directory contains scripts for database schema updates and administrative utilities.

**Do not run these unless instructed to by the plan.**

## How to Run Scripts

Run scripts from the project root directory:

`php migrations/SCRIPT_NAME.php`

---

## Available Scripts

### 1. Schema Migrations

* **`13.1_migrate_roles.php`**
    * **Purpose:** Migrates old static roles to the new RBAC system.
    * **Usage:** `php migrations/13.1_migrate_roles.php`
    * **Status:** Run once after deploying Phase 13 code.

* **`14.1_populate_alliance_structures.php`**
    * **Purpose:** Populates the `alliance_structures_definitions` table with base data.
    * **Usage:** `php migrations/14.1_populate_alliance_structures.php`
    * **Status:** Run once after deploying Phase 14 code.

### 2. Admin Utilities

* **`reset_all_stats.php`**
    * **Purpose:** **GLOBAL RESPEC.** Loops through every user, resets all spent attribute points (Strength, Wealth, etc.) to 0, and sets their available Skill Points exactly equal to their Level.
    * **Usage:** `php migrations/reset_all_stats.php`
    * **Note:** This is destructive. It wipes all player builds.