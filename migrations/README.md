# Migrations

This directory contains one-time scripts used to update our database schema or data.

**Do not run these unless instructed to by the plan.**

## How to Run a Migration

Run scripts from the project root directory:

`php migrations/13.1_migrate_roles.php`

***

**Next Steps:**

Before we can continue with the plan, you **must** run this migration script.

1.  Open your terminal.
2.  Navigate to your project root: `cd /usr/local/var/www/starlight_v2`
3.  Run the script: `php migrations/13.1_migrate_roles.php`

You should see output like:
`Starting alliance role migration...
 Migrating Alliance ID: 1... 
 Migration complete.
  Migrated 1 alliances in 0.01 seconds.`