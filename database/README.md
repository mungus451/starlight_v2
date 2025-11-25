# Database Migrations

This directory contains Phinx migration files for schema evolution.

## Directory Structure

- `migrations/` - Schema migrations (CREATE, ALTER, DROP)
- `seeds/` - Data seeding for testing/development
- `snapshots/` - Schema snapshots for baselining and auditing

## Migration Naming

Migrations follow the pattern: `YYYYMMDDHHMMSS_descriptive_name.php`

Phinx automatically generates the timestamp prefix when you create a migration.

## Common Commands

### Using Docker (Recommended)

```bash
# Check migration status
docker exec starlight_app composer phinx status

# Run all pending migrations
docker exec starlight_app composer phinx migrate

# Rollback last migration
docker exec starlight_app composer phinx rollback

# Create a new migration
docker exec starlight_app composer phinx create MyMigrationName

# Run migrations in testing environment
docker exec starlight_app composer phinx migrate -e testing
```

### Using Local PHP (Without Docker)

If running locally without Docker, first update `.env` to use `DB_HOST=127.0.0.1` and `DB_PORT=3307`, then:

```bash
# Install dependencies (first time)
composer install

# Check migration status
composer phinx status

# Run all pending migrations
composer phinx migrate

# Rollback last migration
composer phinx rollback

# Create a new migration
composer phinx create MyMigrationName
```

## Baseline an Existing Database

If you have an existing database already running in production, use the baseline script:

```bash
# Using Docker
docker exec starlight_app php scripts/phinx_baseline.php

# With snapshot generation
docker exec starlight_app php scripts/phinx_baseline.php --snapshot

# Or locally (if not using Docker)
php scripts/phinx_baseline.php
php scripts/phinx_baseline.php --snapshot
```

This is useful for:
- Onboarding new developers with existing production data
- Transitioning from manual SQL files to managed migrations
- Recording the current schema state before future changes

## Creating Migrations

Always write reversible migrations when possible:

```php
<?php
use Phinx\Migration\AbstractMigration;

class MyMigration extends AbstractMigration
{
    public function up()
    {
        // Create or modify schema
        $table = $this->table('my_table');
        $table->addColumn('new_column', 'string')
              ->save();
    }

    public function down()
    {
        // Reverse the changes
        $table = $this->table('my_table');
        $table->removeColumn('new_column')
              ->save();
    }
}
```

## Best Practices

1. **One logical change per migration** - Makes rollbacks safer
2. **Always test rollback** - Ensure `down()` works before merging
3. **Use transactions** - Phinx wraps migrations in transactions by default
4. **Document non-reversible changes** - Some DDL can't be rolled back safely
5. **Keep data migrations separate** - Use clear naming for data vs schema changes
6. **Test in staging first** - Never run untested migrations in production

## Troubleshooting

### Migration fails mid-execution
- Check `phinxlog` table to see what was applied
- Fix the migration file and re-run
- May need manual cleanup if transaction failed

### "Migration not found" error
- Ensure migration files are in `database/migrations/`
- Check file naming follows Phinx conventions
- Run `composer phinx status` to verify

### Baseline script won't run
- Check that `.env` is configured
- Ensure database is accessible
- Verify `phinxlog` table exists (Phinx creates it automatically)
