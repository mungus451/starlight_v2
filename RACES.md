# Race System Documentation

## Overview

StarlightDominion V2 includes a race system that adds diversity and strategic depth to gameplay. Each race has access to an exclusive resource that can be used for advanced technologies and upgrades.

## Database Schema

### Tables

#### `races`
Stores the definition of each race in the game.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT UNSIGNED | Primary key |
| `name` | VARCHAR(50) | Race name (unique) |
| `exclusive_resource` | VARCHAR(50) | Name of the race's exclusive resource |
| `lore` | TEXT | Background story and context |
| `uses` | TEXT | Gameplay applications of the exclusive resource |

#### `users.race_id`
Foreign key linking a user to their chosen race.

- **Type:** INT UNSIGNED, NULL
- **Constraint:** `fk_user_race` → `races(id)` ON DELETE SET NULL
- **Note:** NULL allows users to exist without a race (for backward compatibility or initial setup)

#### `user_resources` - Race-Exclusive Resource Columns
Four new columns for race-exclusive resources:

| Column | Type | Default | Description |
|--------|------|---------|-------------|
| `whisperium_spice` | DECIMAL(19,4) | 0.0000 | Aridan Nomads exclusive resource |
| `aurorium_crystals` | DECIMAL(19,4) | 0.0000 | Luminarch Order exclusive resource |
| `xenoplasm_biogel` | DECIMAL(19,4) | 0.0000 | Vorax Brood exclusive resource |
| `zerulium_cores` | DECIMAL(19,4) | 0.0000 | Synthien Collective exclusive resource |

## The Four Races

### 1. Aridan Nomads

**Exclusive Resource:** Whisperium Spice

**Lore:** Harvested from their desert world, used for psychic navigation and psionic tech.

**Uses:**
- Tier 4+ warp route plotting
- Advanced medical enhancers

**Gameplay Strategy:** The Aridan Nomads excel at navigation and support roles. Their Whisperium Spice enables access to advanced navigation systems and medical technologies that can keep fleets operational longer.

---

### 2. Luminarch Order

**Exclusive Resource:** Aurorium Crystals

**Lore:** Cultivated from radiant crystal groves attuned to cosmic energy.

**Uses:**
- Advanced energy weapons
- Hyperdrive cores
- Phase shields

**Gameplay Strategy:** The Luminarch Order focuses on energy-based warfare and defense. Aurorium Crystals power the most devastating energy weapons and impenetrable shields in the galaxy.

---

### 3. Vorax Brood

**Exclusive Resource:** Xenoplasm Bio-Gel

**Lore:** A living bio-organic secretion used in biotech fusion.

**Uses:**
- Self-healing armor
- Bio-weapon engineering
- Cybernetic upgrades

**Gameplay Strategy:** The Vorax Brood specializes in biotechnology and regeneration. Their Xenoplasm Bio-Gel creates living systems that adapt and heal, making them incredibly resilient in prolonged conflicts.

---

### 4. Synthien Collective

**Exclusive Resource:** Zerulium Cores

**Lore:** Exotic quantum matter refined from black hole reactors.

**Uses:**
- Quantum AI cores
- Teleportation drives
- Sentient tech constructs

**Gameplay Strategy:** The Synthien Collective harnesses quantum technology and artificial intelligence. Zerulium Cores enable reality-bending technologies like teleportation and autonomous combat systems.

---

## Migration Details

**Migration File:** `database/migrations/20251215024921_add_race_support.php`

**Applies:**
1. Creates `races` table
2. Inserts all 4 races with their lore and uses
3. Adds `race_id` column to `users` table with foreign key constraint
4. Adds 4 race-exclusive resource columns to `user_resources` table

**Reversible:** Yes, the migration uses Phinx's `change()` method for automatic rollback support.

## Integration Notes

### For Developers

When implementing race-specific features:

1. **Race Selection:** Players should select their race during character creation or through a one-time choice mechanic
2. **Resource Generation:** Only the player's race-specific resource should be harvestable/producible
3. **Cross-Race Trading:** Consider implementing a trading system where players can exchange race-exclusive resources
4. **Balance:** Each race's exclusive resource should have comparable value and strategic importance

### Example Queries

**Get a user's race information:**
```sql
SELECT u.character_name, r.name as race_name, r.exclusive_resource
FROM users u
LEFT JOIN races r ON u.race_id = r.id
WHERE u.id = ?;
```

**Get a user's race-exclusive resource amount:**
```sql
SELECT 
    r.exclusive_resource,
    CASE r.id
        WHEN 1 THEN ur.whisperium_spice
        WHEN 2 THEN ur.aurorium_crystals
        WHEN 3 THEN ur.xenoplasm_biogel
        WHEN 4 THEN ur.zerulium_cores
    END as resource_amount
FROM users u
JOIN races r ON u.race_id = r.id
JOIN user_resources ur ON u.id = ur.user_id
WHERE u.id = ?;
```

## Future Considerations

- **Race-Specific Abilities:** Passive bonuses or active abilities unique to each race
- **Race Diplomacy:** Tension or alliance modifiers between different races
- **Race Territories:** Designated home sectors or planets for each race
- **Race Technology Trees:** Unique upgrade paths utilizing exclusive resources
- **Cross-Race Resource Exchange:** Market or trading mechanics for rare resources

## See Also

- `database/migrations/20251215024921_add_race_support.php` - The migration file
- `database/migrations/20251215024921_add_race_support.sql` - Raw SQL reference
