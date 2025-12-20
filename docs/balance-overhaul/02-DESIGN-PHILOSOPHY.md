---
layout: default
title: Design Philosophy
---

# Design Philosophy: Building a Fair, Engaging Game

## Core Principle

**A game is only competitive if a new player has a reasonable path to compete with veterans through superior strategy and activity—not by arriving first.**

---

## Guiding Tenets

### 1. **Effort Over Timing**
An engaged player starting Day 30 should be able to challenge a lazy Day 1 veteran by Day 60 through superior play.

**Anti-pattern**: "You should have been here on launch day"

### 2. **Conflict Over Hoarding**
Optimal strategy should be active engagement (attacks, espionage, alliances), not passive wealth accumulation.

**Anti-pattern**: "Build accounting firm to max, bank everything, turtle"

### 3. **Risk-Reward Balance**
Actions with higher rewards must carry proportional costs or risks.

**Anti-pattern**: "Spam attacks with 0.5% losses to steal millions"

### 4. **Bounded Power Scaling**
Veteran advantages should be **linear**, not **exponential**.

**Anti-pattern**: "Level 50 accounting firm grants 1,147% income boost"

### 5. **New Player Onboarding**
The first 7 days should be learning/building, not survival against predators.

**Anti-pattern**: "Farm newbies for easy plunder"

### 6. **Economic Circulation**
Wealth should flow through the economy via transactions, taxes, and costs—not accumulate infinitely in bank accounts.

**Anti-pattern**: "100B sitting in bank earning 144%/day interest"

---

## Design Goals

### Goal 1: Eliminate Exponential Wealth Gap
**Target**: Wealth Gini coefficient stays below 0.65 at Day 90

**Mechanisms**:
- Zero bank interest (replace with insurance perks)
- Zero alliance treasury interest
- Change accounting firm from multiplicative to additive
- Hard-cap total income multipliers at +100% (2× base)

**Success Metric**: Top 1% of players hold <35% of total wealth at Day 90

---

### Goal 2: Make Combat Consequential
**Target**: Combat outcomes significantly impact both attacker and defender economies

**Mechanisms**:
- Raise casualty scalar from 0.1 → 0.22 (2.2× more deaths)
- Reduce nanite forge max from 50% → 25%
- Add deployment costs (pay to project force)
- Add unit maintenance (large armies cost upkeep)

**Success Metric**: Average battle costs attacker 5–10% of their gross income for that turn

---

### Goal 3: Reward Active Engagement
**Target**: Daily active players pull ahead of inactive/turtle players

**Mechanisms**:
- Weekly activity bonuses (+15% max income for engagement)
- Readiness decay (idle armies lose 0.1%/turn effectiveness after 7d grace)
- Alliance benefits tied to active contribution, not just membership

**Success Metric**: Players with 1+ attack/day average 1.5× net worth of 0 attack/day players by Day 60

---

### Goal 4: Protect New Players Without Alt Abuse
**Target**: 7-day full protection, 30-day graduated protection, zero alt farming

**Mechanisms**:
- Full attack immunity for 7 days
- Graduated vault protection (100% → 90% → 50% over 30 days)
- Reduced plunder during protection (25% of normal)
- Anti-laundering rules (no outbound transfers while protected)

**Success Metric**: New player retention >50% past Day 7 (vs current <20%)

---

### Goal 5: Create Catch-Up Mechanics
**Target**: A Day 30 newbie can compete with a Day 1 veteran by Day 90 if more active

**Mechanisms**:
- Graduated vault protection (veterans lose 50% protection, incentivizing offense)
- Maintenance costs scale with army size (late-game armies expensive)
- Activity bonuses favor engagement over time-in-game

**Success Metric**: Power gap between 60-day engaged vs 60-day casual is ≤2.0× (not 6×)

---

### Goal 6: Cap Multiplier Stacking
**Target**: No runaway scaling—all bonuses hit hard caps

**Mechanisms**:
- Income multiplier budget: +100% max (structures 50%, alliance 30%, stats 20%, activity 15%)
- Combat multiplier budget: +120% max (structures 50%, equipment 40%, stats 30%)
- Function to clamp bonuses when budget exceeded

**Success Metric**: No player exceeds 2× base income regardless of level/upgrades by Day 180

---

## Design Constraints

### What We CANNOT Do

1. **Remove progression entirely** - Veterans should still have advantages, just not insurmountable ones
2. **Punish smart play** - Optimization should be rewarded, but not exponentially
3. **Make PvP mandatory** - Solo players should be viable, just not optimal
4. **Invalidate existing player investments** - Migration must preserve relative progress
5. **Create daily login hostage mechanics** - Miss a day? Should not ruin your account

### What We MUST Preserve

1. **Turn-based economy** - Core loop of "wait for turn, collect resources, make decisions"
2. **Alliance cooperation** - Groups should have advantages
3. **Combat risk** - Attacking should always have stakes
4. **Research/upgrade value** - Progression should feel rewarding
5. **Espionage utility** - Intel gathering should remain valuable

---

## Philosophy: Linear vs Exponential Growth

### ❌ Exponential Growth (Current System)
```
Income = Base × (1.05 ^ Accounting_Level) × (1 + 0.01 × Wealth_Stat) × ...
```
**Problem**: Stacking multipliers compound infinitely

### ✅ Linear Growth (Proposed System)
```
Income = Base × (1 + Structure_Bonus + Alliance_Bonus + Stat_Bonus + Activity_Bonus)
Where: Structure_Bonus ≤ 0.50, Alliance_Bonus ≤ 0.30, Stat_Bonus ≤ 0.20, Activity_Bonus ≤ 0.15
Total ≤ 1.00 (2× base max)
```
**Solution**: All bonuses are additive and capped

---

## Economic Model Comparison

### Current System: Interest-Based Banking
- Players earn 0.03%/turn on banked credits
- Alliance treasuries earn 0.5%/turn
- Accounting firm multiplies ALL income by 1.05^level
- **Result**: Rich get richer exponentially

### Proposed System: Insurance-Based Banking
- Players earn **ZERO** interest on banked credits
- Banking provides **insurance perks**:
  - 20% cheaper rebuild on defeat
  - 10% repair discount
  - Vault protection tiers
- **Result**: Banking provides safety, not growth

**Philosophy**: Your wealth should grow from **economic activity** (structures, workers, combat spoils), not from **sitting on a pile of gold**.

---

## Friction Systems: Why Upkeep Matters

### Current System: Zero Friction
```
Train 100k soldiers → Pay 1.5B once → Keep forever at zero cost
```
**Result**: Veterans hoard massive armies, turtling is optimal

### Proposed System: Proportional Friction
```
Train 100k soldiers → Pay 1.5B once → Pay 300k/turn maintenance
```
**Result**: Large armies require economic commitment to maintain

**Philosophy**: Force projection should have **ongoing cost**, not just **initial cost**. A 500k army should require a 500k economy to sustain.

---

## Activity vs Time-in-Game

### Anti-Pattern: Calendar Hostage
❌ "Daily login streaks give permanent advantages"  
❌ "Miss a day, fall permanently behind"

### Proposed Pattern: Rolling Windows
✅ "Weekly activity bonuses with 1-week catch-up"  
✅ "Readiness decay for idle armies (grace period)"  
✅ "Engagement matters, but missing a week doesn't kill you"

**Philosophy**: Reward active players, but don't punish people with real-world obligations.

---

## Competitive Balance Framework

### Power Gap Targets

| Timeframe | Engaged vs Casual | Veteran vs Newbie |
|-----------|-------------------|-------------------|
| Day 30 | ≤1.5× | ≤3.0× |
| Day 60 | ≤2.0× | ≤4.0× |
| Day 90 | ≤2.5× | ≤5.0× |

**Rationale**: 
- Engaged players deserve advantage, but not dominance
- Veterans have head start, but newbies can catch up
- By Day 90, skill/activity > timing

---

## Testing Philosophy

### Traditional Approach (Flawed)
✅ Unit tests pass  
✅ Integration tests pass  
❌ No simulation of long-term economy  
**Result**: Launch → discover oligarchy at Day 60 → too late

### Proposed Approach (Robust)
✅ Unit tests for all services  
✅ Integration tests for service interactions  
✅ **90-day deterministic simulation** with casual/engaged/turtle archetypes  
✅ **KPI tripwires** (Gini, wealth concentration, power gaps)  
**Result**: Discover imbalance in testing → fix before launch

---

## Success Criteria

### Quantitative KPIs
- Wealth Gini coefficient <0.65 at Day 90
- Top 1% wealth share <35% at Day 90
- Power gap engaged/casual ≤2.0× at Day 60
- Median time to Economy 10: 10 days
- Median time to Level 20: 45 days
- Average attacks per active player: 1.0+/day

### Qualitative Goals
- New players feel progression is achievable
- Veterans feel rewarded but not invincible
- PvP interactions are frequent and strategic
- Alliances provide meaningful benefits
- Alt farming is economically nonviable
- Balance is transparent and testable

---

## Implementation Mindset

### Principle: Measure → Simulate → Validate → Deploy

1. **Measure baseline** - Current system simulation
2. **Simulate changes** - Proposed system with same archetypes
3. **Validate KPIs** - Do numbers hit targets?
4. **Deploy with monitoring** - Real-time KPI tracking post-launch

**Philosophy**: Balance is a hypothesis. Validate it with data before committing.

---

Next: [03-BANKING-OVERHAUL.md](03-BANKING-OVERHAUL.md)
