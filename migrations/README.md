# Legacy Migrations

⚠️ **DEPRECATED**: This directory contains legacy one-time migration scripts.

**New migrations should use Phinx** in the `database/migrations/` directory.

## Migration System Update

This project now uses **Phinx** for database migrations. All new schema changes should be managed through Phinx migrations.

### For Legacy Scripts

The script `13.1_migrate_roles.php` has been converted to a Phinx migration:
- Location: `database/migrations/20251115120000_migrate_alliance_roles_to_role_system.php`
- Run via: `composer phinx migrate`

### Migration Workflow

See `database/README.md` for the new migration workflow and best practices.

Common commands:
```bash
# Check status
composer phinx status

# Run migrations
composer phinx migrate

# Create new migration
composer phinx create MyMigrationName
```

### Baseline Existing Databases

If you have an existing database and want to adopt Phinx:

```bash
php scripts/phinx_baseline.php
```

This marks all migrations as applied without executing them, perfect for production databases that already have the schema.

---

## Legacy Script (For Reference Only)

### 13.1_migrate_roles.php

**Status**: Converted to Phinx migration  
**Phinx Version**: `20251115120000_migrate_alliance_roles_to_role_system.php`

This script created default alliance roles (Leader, Member, Recruit) for existing alliances.

**Do not run this script directly** - use `composer phinx migrate` instead.
