#!/bin/bash

# Phinx Migration Helper Script
# Convenience wrapper for common Phinx operations with guidance

set -e

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if vendor/bin/phinx exists
if [ ! -f "vendor/bin/phinx" ]; then
    echo -e "${RED}Error: Phinx not found. Run 'composer install' first.${NC}"
    exit 1
fi

# Check if .env exists
if [ ! -f ".env" ]; then
    echo -e "${RED}Error: .env file not found.${NC}"
    echo "Copy .env.example to .env and configure your database credentials."
    exit 1
fi

# Show usage if no arguments
if [ $# -eq 0 ]; then
    echo "Phinx Migration Helper"
    echo ""
    echo "Usage: ./scripts/phinx_migrate.sh [command] [options]"
    echo ""
    echo "Commands:"
    echo "  status              Show migration status"
    echo "  migrate             Run all pending migrations"
    echo "  rollback            Rollback the last migration"
    echo "  create <name>       Create a new migration file"
    echo "  baseline            Mark existing DB as migrated (for existing databases)"
    echo "  snapshot            Generate schema snapshot"
    echo ""
    echo "Examples:"
    echo "  ./scripts/phinx_migrate.sh status"
    echo "  ./scripts/phinx_migrate.sh migrate"
    echo "  ./scripts/phinx_migrate.sh create AddUserEmailIndex"
    echo "  ./scripts/phinx_migrate.sh baseline"
    echo ""
    exit 0
fi

COMMAND=$1
shift

case "$COMMAND" in
    status)
        echo -e "${GREEN}Checking migration status...${NC}"
        vendor/bin/phinx status "$@"
        ;;
        
    migrate)
        echo -e "${GREEN}Running migrations...${NC}"
        vendor/bin/phinx migrate "$@"
        echo -e "${GREEN}✓ Migrations complete${NC}"
        ;;
        
    rollback)
        echo -e "${YELLOW}Rolling back last migration...${NC}"
        echo "This will reverse the most recent migration."
        read -p "Continue? (yes/no): " confirm
        if [ "$confirm" = "yes" ]; then
            vendor/bin/phinx rollback "$@"
            echo -e "${GREEN}✓ Rollback complete${NC}"
        else
            echo "Aborted."
        fi
        ;;
        
    create)
        if [ $# -eq 0 ]; then
            echo -e "${RED}Error: Migration name required${NC}"
            echo "Usage: ./scripts/phinx_migrate.sh create <MigrationName>"
            exit 1
        fi
        
        MIGRATION_NAME=$1
        echo -e "${GREEN}Creating migration: $MIGRATION_NAME${NC}"
        vendor/bin/phinx create "$MIGRATION_NAME"
        ;;
        
    baseline)
        echo -e "${YELLOW}Running baseline script...${NC}"
        echo "This will mark existing migrations as applied without running them."
        php scripts/phinx_baseline.php "$@"
        ;;
        
    snapshot)
        echo -e "${GREEN}Generating schema snapshot...${NC}"
        php scripts/phinx_snapshot.php "$@"
        ;;
        
    *)
        echo -e "${RED}Unknown command: $COMMAND${NC}"
        echo "Run without arguments to see usage."
        exit 1
        ;;
esac
