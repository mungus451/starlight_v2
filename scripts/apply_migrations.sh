#!/usr/bin/env bash
# Applies database.sql (V2 migrations) to running MariaDB container
set -euo pipefail

if [ "$1" = "help" ] || [ "$#" -gt 0 ] && [ "$1" != "--" ]; then
  echo "Usage: ./scripts/apply_migrations.sh"
  echo "This will run database.sql against the running starlight_db container."
  exit 0
fi

# Ensure DB container is running
if ! docker compose ps -q db >/dev/null; then
  echo "starlight_db container not found or not running. Start with: docker compose up -d"
  exit 1
fi

# Apply migrations
docker exec -i starlight_db mysql -u sd_admin -pstarlight starlightDB < database.sql || {
  echo "Applying migrations failed. You can inspect the SQL file and run the migrate command manually:" \
       "docker exec -i starlight_db mysql -u sd_admin -pstarlight starlightDB < database.sql"
  exit 1
}

echo "Migrations applied successfully."
