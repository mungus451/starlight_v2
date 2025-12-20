---
layout: default
title: Balance Overhaul Documentation
---

# Balance Overhaul Documentation Index

This folder contains comprehensive documentation for the proposed anti-oligarchy balance overhaul for StarlightDominion V2.

## Reading Order

1. **[01-PROBLEM-STATEMENT.md](01-PROBLEM-STATEMENT.md)** - Core issues with current balance
2. **[02-DESIGN-PHILOSOPHY.md](02-DESIGN-PHILOSOPHY.md)** - Guiding principles and goals
3. **[03-BANKING-OVERHAUL.md](03-BANKING-OVERHAUL.md)** - Eliminating compounding interest
4. **[04-PROTECTION-SYSTEMS.md](04-PROTECTION-SYSTEMS.md)** - Vault and newbie protection
5. **[05-FRICTION-SYSTEMS.md](05-FRICTION-SYSTEMS.md)** - Maintenance and deployment costs
6. **[06-ENGAGEMENT-SYSTEMS.md](06-ENGAGEMENT-SYSTEMS.md)** - Activity bonuses and readiness
7. **[07-MULTIPLIER-CAPS.md](07-MULTIPLIER-CAPS.md)** - Preventing runaway scaling
8. **[08-TESTING-FRAMEWORK.md](08-TESTING-FRAMEWORK.md)** - Simulation and KPI monitoring
9. **[09-IMPLEMENTATION-PLAN.md](09-IMPLEMENTATION-PLAN.md)** - Code changes required
10. **[10-MIGRATION-STRATEGY.md](10-MIGRATION-STRATEGY.md)** - Transitioning existing players

## Quick Reference

### Current Problems
- Compounding interest creates exponential wealth gap (player bank 0.03%/turn + alliance 0.5%/turn)
- Accounting firm multiplier compounds infinitely (1.05^level)
- No upkeep costs - armies are free forever
- Low casualties (10% scalar) encourage hoarding
- No newbie protection enables predatory alt-farming
- Multipliers can stack infinitely

### Proposed Solutions
- **Zero interest** - Replace with insurance model (perks, not growth)
- **Graduated vault protection** - 100% protected <7d, 90% <30d, 50% veteran
- **Unit maintenance** - 2k free garrison, then per-unit costs capped at 40% income
- **Deployment costs** - Pay when attacking/spying, not just existing
- **Readiness decay** - Idle armies lose 0.1%/turn effectiveness after 7d grace
- **Activity bonuses** - Weekly engagement rewards (+15% cap) with anti-grind
- **Multiplier budgets** - Hard caps on stacked bonuses (income +100%, combat +120%)
- **Raise casualty scalar** - 0.1 → 0.22 (make battles consequential)
- **Accounting firm fix** - Change from 1.05^level to additive 1%/level with DR

## Status

**Status**: Proposal Only - Config Changes Reverted  
**Branch**: `game-balance`  
**Last Updated**: 2025-12-18

All proposed changes have been documented but NOT implemented. The `game_balance.php` file has been reverted to its original state with:
- `bank_interest_rate = 0.0003`
- `alliance_treasury.bank_interest_rate = 0.005`
- `accounting_firm_multiplier = 1.05`
- `global_casualty_scalar = 0.1`
- No anti-oligarchy systems

## Implementation Checklist

- [ ] Review all documentation files
- [ ] Stakeholder approval on design philosophy
- [ ] Build simulation framework (see 08-TESTING-FRAMEWORK.md)
- [ ] Run 90-day archetype simulations
- [ ] Validate KPI tripwires with baseline data
- [ ] Implement config changes (see 09-IMPLEMENTATION-PLAN.md)
- [ ] Wire services to respect new config sections
- [ ] Write integration tests
- [ ] Plan player migration (see 10-MIGRATION-STRATEGY.md)
- [ ] Staged rollout with monitoring
- [ ] Post-launch KPI tracking

## Contact

For questions about this balance overhaul proposal, contact the game balance architect.
