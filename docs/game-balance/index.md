# Game Balance Overview

Core formulas and constants live in `config/game_balance.php`, with detailed overhaul proposals under `docs/balance-overhaul/`.

## Current Balance Issue: Casualty vs Production Paradox

### The Problem

We've identified a **progression paradox** where both of these statements are true simultaneously:

1. **Documentation says**: "Casualties are too LOW (0.1 scalar), combat is toothless"
2. **Players complain**: "Cannot replenish units fast enough"

### Root Cause

Both complaints are valid at **different progression stages**:

- **Early Game (Population Level 1-10)**:
  - Production: 288-2,880 citizens/day
  - Battle casualties: ~1,000 units (medium battle)
  - Result: Takes 1-4 days to recover from one battle
  - **Player experience**: "I can't fight, I run out of troops!"

- **Late Game (Population Level 30+)**:
  - Production: 8,640+ citizens/day
  - Battle casualties: ~1,000 units (same battle)
  - Result: Can fight 8+ battles per day
  - **Player experience**: "Combat doesn't matter, I have infinite troops"

### Solution: Balanced Approach

We've implemented a **Balance Simulation Engine** to test changes before deployment. Based on analysis:

**Recommended Configuration Changes:**

```php
// In config/game_balance.php

// Turn Processor
'turn_processor' => [
    'citizen_growth_per_pop_level' => 3,  // Was: 1 (3× increase)
    // ... rest unchanged
],

// Attack System  
'attack' => [
    'global_casualty_scalar' => 0.15,  // Was: 0.1 (50% increase)
    
    // Nanite Forge
    'nanite_casualty_reduction_per_level' => 0.005,  // Was: 0.01
    'max_nanite_casualty_reduction' => 0.25,        // Was: 0.50
],
```

**Why These Numbers?**

| Level | Citizens/Day (Old) | Citizens/Day (New) | Impact |
|-------|-------------------|--------------------|--------|
| 1 | 288 | 864 | Early game: Can fight every 1-2 days ✅ |
| 5 | 1,440 | 4,320 | Can sustain 1+ attack/day ✅ |
| 10 | 2,880 | 8,640 | Can sustain 3-8 attacks/day ✅ |
| 30 | 8,640 | 25,920 | Healthy combat rate ✅ |

**Casualty Impact:**
- Winner losses: 0.5%-1.5% → 0.75%-2.25% (more consequential)
- Loser losses: 2%-4% → 3%-6% (significant but not devastating)
- Late-game invulnerability prevented (Nanite cap 25% vs 50%)

## Testing Tools

### Quick Analysis (Any PHP Version)
```bash
php tests/simple_balance_analysis.php
```

### Configuration Comparison
```bash
php tests/compare_balance_configs.php
```

### Full Simulation (Requires PHP 8.4+)
```bash
docker compose exec app php tests/balance_simulation_test.php
```

## Documentation

- [Balance Simulation Engine](simulation-engine.md) - Comprehensive testing framework
- [Balance Overhaul Proposals](../balance-overhaul/00-INDEX.md) - Detailed analysis and proposals
- [Implementation Summary + CI Plan](IMPLEMENTATION_SUMMARY.md) - Recommended changes and CI/CD balance testing plan

## Next Steps

1. Review proposed changes with team
2. Run simulations to validate
3. Deploy to dev server
4. Monitor metrics for 7 days
5. Gather player feedback
6. Adjust if needed
7. Deploy to production

Balance is not set-and-forget. It requires **continuous monitoring and adjustment**.
