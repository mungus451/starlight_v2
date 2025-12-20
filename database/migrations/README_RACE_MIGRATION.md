# Race Support Migration Guide

## Overview

This migration adds race support to StarlightDominion V2, including:

1. **`races` table** - Defines the 5 playable races
2. **`users.race_id`** - Links users to their chosen race
3. **Race-exclusive resources** - 5 new columns in `user_resources`

## Migration Files

- **Phinx Migration:** `20251215024921_add_race_support.php`
- **Documentation:** `/RACES.md`

## Running the Migration

### Using Docker (Recommended)

```bash
# Check current migration status
docker exec starlight_app composer phinx status

# Run the migration
docker exec starlight_app composer phinx migrate

# Verify it was applied
docker exec starlight_app composer phinx status
```

### Using Local PHP

```bash
# Check current migration status
composer phinx status

# Run the migration
composer phinx migrate

# Verify it was applied
composer phinx status
```

## What Gets Created

### New Table: `races`

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key (1-5) |
| name | VARCHAR(50) | Race name (unique) |
| exclusive_resource | VARCHAR(50) | Name of exclusive resource |
| lore | TEXT | Background story |
| uses | TEXT | Gameplay applications |

**Data Inserted:**
1. Aridan Nomads → Whisperium Spice
2. Luminarch Order → Aurorium Crystals
3. Vorax Brood → Xenoplasm Bio-Gel
4. Synthien Collective → Zerulium Cores
5. The Synthera → Voidsteel Alloy

### Modified Table: `users`

**Added Column:**
- `race_id` INT UNSIGNED NULL - Foreign key to `races(id)`
- Constraint: `fk_user_race` with ON DELETE SET NULL

### Modified Table: `user_resources`

**Added Columns (all DECIMAL(19,4) NOT NULL DEFAULT 0.0000):**
- `whisperium_spice` - For Aridan Nomads
- `aurorium_crystals` - For Luminarch Order
- `xenoplasm_biogel` - For Vorax Brood
- `zerulium_cores` - For Synthien Collective
- `voidsteel_alloy` - For The Synthera

## Rollback

If you need to rollback this migration:

```bash
# Using Docker
docker exec starlight_app composer phinx rollback

# Using Local PHP
composer phinx rollback
```

This will:
1. Remove the 5 resource columns from `user_resources`
2. Remove the `race_id` column and foreign key from `users`
3. Drop the `races` table

## Verification Queries

After running the migration, verify it worked:

```sql
-- Check races table
SELECT * FROM races;

-- Check users table structure
DESCRIBE users;

-- Check user_resources table structure
DESCRIBE user_resources;

-- Verify foreign key exists
SELECT 
    CONSTRAINT_NAME, 
    TABLE_NAME, 
    COLUMN_NAME, 
    REFERENCED_TABLE_NAME, 
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE CONSTRAINT_NAME = 'fk_user_race';
```

## Next Steps

After applying this migration:

1. **Update Entity Classes:**
   - Add `race_id` property to `User` entity
   - Add race-exclusive resource properties to `UserResource` entity

2. **Update Repositories:**
   - Update queries in `UserRepository` to include `race_id`
   - Update queries in `ResourceRepository` to include new resource columns

3. **Implement Race Selection:**
   - Create race selection UI during character creation
   - Add service methods for race selection/management

4. **Implement Resource Mechanics:**
   - Add production mechanisms for race-exclusive resources
   - Implement usage/consumption in relevant game systems

5. **Update Game Balance:**
   - Define production rates in `/config/game_balance.php`
   - Define usage costs and benefits

## See Also

- `/RACES.md` - Complete race system documentation
- `/database/README.md` - General migration documentation
- Phinx documentation: https://book.cakephp.org/phinx/0/en/
