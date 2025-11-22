# Phinx Migration Quick Reference

## Setup Complete ✅

Phinx is configured and ready to use! Migrations are tracked in `database/migrations/`.

## Current Migration Status

```
✓ 20251115000000 - BaselineProductionSchema (marker)
✓ 20251115120000 - MigrateAllianceRolesToRoleSystem
```

## Common Workflows

### Check Status
```bash
docker exec starlight_app composer phinx status
```

### Run New Migrations
```bash
docker exec starlight_app composer phinx migrate
```

### Create Migration
```bash
# Creates timestamped file in database/migrations/
docker exec starlight_app composer phinx create AddUserEmailIndex
```

### Rollback
```bash
# Rollback last migration
docker exec starlight_app composer phinx rollback

# Rollback to specific version
docker exec starlight_app composer phinx rollback -t 20251115000000
```

### Testing Migrations
```bash
# Test in test environment
docker exec starlight_app composer phinx migrate -e testing
docker exec starlight_app composer phinx rollback -e testing
```

## Helper Scripts

### Baseline Script (For Existing Databases)
```bash
# Mark migrations as applied without running
docker exec starlight_app php scripts/phinx_baseline.php --snapshot
```

### Schema Snapshot
```bash
# Generate SQL schema snapshot (auto-detects mysqldump availability)
docker exec starlight_app php scripts/phinx_snapshot.php

# Generate JSON schema snapshot (detailed with indexes, FKs, etc)
docker exec starlight_app php scripts/phinx_snapshot.php --format=json

# Custom output location
docker exec starlight_app php scripts/phinx_snapshot.php --out=database/snapshots/baseline.sql
```

**Note:** The snapshot script automatically falls back to `SHOW CREATE TABLE` if `mysqldump` is not available in the container.

### Migration Helper
```bash
# Convenience wrapper
./scripts/phinx_migrate.sh status
./scripts/phinx_migrate.sh migrate
./scripts/phinx_migrate.sh create MyMigration
```

## Migration Best Practices

1. **One change per migration** - atomic, reversible changes
2. **Test `down()` method** - ensure rollbacks work
3. **Use idempotency checks** - migrations should be re-runnable
4. **Document breaking changes** - add comments for non-reversible operations
5. **Baseline production first** - don't re-run existing schema

## File Locations

- Config: `config/phinx.php`
- Migrations: `database/migrations/`
- Seeds: `database/seeds/`
- Snapshots: `database/snapshots/`
- Scripts: `scripts/phinx_*.php`

## Troubleshooting

### "phinxlog does not exist"
Run `composer phinx migrate` to initialize Phinx (creates phinxlog table automatically).

### Connection errors in Docker
Ensure `.env` uses `DB_HOST=db` for Docker networking.

### Connection errors locally
Update `.env` to use `DB_HOST=127.0.0.1` and `DB_PORT=3307`.

## Next Steps

- Convert `database_v1.sql` to domain migrations
- Convert `database.sql` to fine-grained ALTER migrations
- Add CI pipeline for migration testing
- Set up automatic snapshots in CI
