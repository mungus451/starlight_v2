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

**Note:** External ports can be customized by setting `DOCKER_APP_PORT`, `DOCKER_DB_PORT`, and `DOCKER_REDIS_PORT` in your `.env` file. If not set, the defaults listed above will be used.

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
docker exec starlight_app composer phinx status

# Run pending migrations
docker exec starlight_app composer phinx migrate

# Create new migration
docker exec starlight_app composer phinx create MyMigrationName

# Rollback last migration
docker exec starlight_app composer phinx rollback
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
