# Docker Setup for StarlightDominion V2

## Quick Start

### Build and Run with Docker Compose

```bash
# Copy the example environment file
cp .env.example .env

# (Optional) Customize Docker ports by uncommenting and modifying:
# DOCKER_APP_PORT, DOCKER_DB_PORT, DOCKER_REDIS_PORT in .env

# Build and start all services
docker compose up -d --build

# View logs
docker compose logs -f

# Stop all services
docker compose down
```

The application will be available at: http://localhost:8080

## Services

- **app**: PHP 8.4 + Apache web server (default port 8080)
- **db**: MariaDB 11 database (default port 3307)
- **redis**: Redis in-memory data store (default port 6379) — required for sessions, CSRF protection, and rate limiting
- **cron**: Background process for turn processing (runs every 5 minutes)
- **docs**: MkDocs documentation server (default port 8000) — for local preview of documentation

**Note:** External ports can be customized by setting `DOCKER_APP_PORT`, `DOCKER_DB_PORT`, `DOCKER_REDIS_PORT`, and `DOCKER_DOCS_PORT` in your `.env` file. If not set, the defaults listed above will be used.

## Database

The `database.sql` file contains incremental ALTER statements documenting the migration from V1 to V2 structure. The live database already has the complete V2 schema.

**For new installations:** The database volume will persist the existing schema.
**For migrations:** Apply `database.sql` manually to transform V1 to V2.

### Access MariaDB

```bash
# From host machine (substitute values from your .env file)
mysql -h 127.0.0.1 -P 3307 -u sd_admin -pstarlight starlightDB

# From inside container
docker compose exec db mysql -u sd_admin -pstarlight starlightDB
```

**Note:** Database credentials are configured via environment variables in your `.env` file. Replace the values above with your actual credentials from the `MYSQL_USER`, `MYSQL_PASSWORD`, and `MYSQL_DATABASE` variables.

## Development Workflow

### View Application Logs
```bash
docker compose logs -f app
```

### View Cron Logs
```bash
docker compose logs -f cron
# Or check the log file
tail -f logs/cron.log
```

### Run Manual Turn Processing
```bash
docker compose exec app php cron/process_turn.php
```

### Install New Composer Dependencies
```bash
docker compose exec app composer install
```

### Run Migrations
```bash
# Check migration status
docker compose exec app composer phinx status

# Run pending migrations
docker compose exec app composer phinx migrate

# Create new migration
docker compose exec app composer phinx create MyMigrationName

# Rollback last migration
docker compose exec app composer phinx rollback
```

### Access Container Shell
```bash
docker compose exec app bash
```

## Rebuilding

After changing dependencies or Dockerfile:

```bash
docker compose down
docker compose up -d --build
```

## Documentation Preview

The `docs` service runs MkDocs to serve the documentation locally with live reload:

```bash
# Start only the docs service
docker compose up docs

# Or start all services including docs
docker compose up -d

# View docs at:
# http://localhost:8000
```

The docs service watches for changes in the `/docs` folder and automatically rebuilds.

### Docs-only Development

If you only want to work on documentation without running the full app:

```bash
# Start only docs service
docker compose up docs

# Stop docs service
docker compose stop docs
```

## Clean Reset

To completely reset the database and start fresh:

```bash
docker compose down -v
docker compose up -d --build
```

The `-v` flag removes volumes, including the database data.

## Production Notes

For production deployment:

1. Update `.env` with production values
2. Set `APP_ENV=production` in your `.env` file
3. Use proper secrets management (not hardcoded passwords)
4. Consider using Docker secrets or environment variables
5. Set up proper backup strategy for `db_data` volume
6. Use a reverse proxy (nginx/traefik) in front of Apache
