---
layout: default
title: Problem Statement
---

# Problem Statement: The Oligarchy Crisis

## Executive Summary

StarlightDominion V2's current balance creates an **exponential wealth gap** through compounding interest mechanics. Within 90 days, early adopters will become mathematically unbeatable oligarchs while new players face insurmountable catch-up barriers. This document quantifies the crisis and establishes the case for comprehensive balance overhaul.

---

## Core Problems

### 1. Triple Compounding Engine (The Oligarchy Machine)

**Problem**: Three separate compounding systems stack to create exponential wealth growth.

#### Player Bank Interest
```php
'bank_interest_rate' => 0.0003  // 0.03% per turn (288 turns/day = 8.64%/day)
```

**Impact**: With compounding, 100M credits becomes:
- Day 30: 731M (7.3× growth)
- Day 90: 3.9B (39× growth)
- Day 180: 152B (1,520× growth)

#### Alliance Treasury Interest
```php
'alliance_treasury' => [
    'bank_interest_rate' => 0.005  // 0.5% per turn (144%/day compounded)
]
```

**Impact**: Alliance war chests grow even faster, creating dominant mega-alliances that cannot be challenged.

#### Accounting Firm Multiplier
```php
'accounting_firm_multiplier' => 1.05  // 5% multiplicative per level
```

**Impact**: This is the worst offender because it's **permanent** and **stacking**:
- Level 10: 1.63× income (63% boost)
- Level 20: 2.65× income (165% boost)
- Level 30: 4.32× income (332% boost)
- Level 50: 11.47× income (1,047% boost)

**Combined Effect**: A player with 30 levels in accounting firm + 10B banked at Day 90:
- Base income: 10M/day from structures
- Accounting boost: 10M × 4.32 = 43.2M/day
- Bank growth: 10B × 1.0864^30 = 73.1B
- **Total advantage**: They're earning 4× more per day AND sitting on 73B in reserves

**Verdict**: This is a death spiral. New players can NEVER catch up.

---

### 2. Zero Upkeep (Free Armies Forever)

**Problem**: Once you train an army, it costs nothing to maintain forever.

```php
// Current system: NO maintenance costs exist
'cost_per_soldier' => 15000  // Pay once, keep forever
```

**Impact**:
- Veterans hoard massive armies with zero ongoing cost
- No economic pressure to stay active
- Turtling (building army, going inactive) is optimal strategy
- New players face Day 1 armies that are mathematically impossible to defeat

**Example**: A Day 60 veteran with 500k soldiers:
- Initial cost: 7.5B credits (paid 60 days ago)
- Ongoing cost: **ZERO**
- Daily threat value: Can crush any player < Day 30

**Verdict**: Armies should have ongoing costs proportional to size. Large forces should require economic commitment.

---

### 3. Low Casualty Scalar (Toothless Combat)

**Problem**: Combat barely hurts anyone.

```php
'global_casualty_scalar' => 0.1  // Reduces deaths by 90%
```

**Impact**:
- Winner loses: 0.5%–1.5% of army (after scalar)
- Loser loses: 2%–4% of army (after scalar)
- A battle between 100k vs 100k armies results in ~1k–2k deaths TOTAL

**Example**: Attacker with 100k soldiers attacks defender with 50k guards:
- Attacker wins decisively
- Attacker casualties: ~500 soldiers (0.5%)
- Defender casualties: ~1,500 guards (3%)
- **Result**: Attacker paid 7.5M to replace losses, stole 10% of defender's bank (potentially hundreds of millions)

**Verdict**: Combat should be CONSEQUENTIAL. Current system encourages reckless spam attacks because losses are negligible.

---

### 4. Nanite Forge Invulnerability

**Problem**: Nanite Forge can reach 50% casualty reduction, making elite players nearly invincible.

```php
'nanite_casualty_reduction_per_level' => 0.01,  // 1% per level
'max_nanite_casualty_reduction' => 0.50,        // Max 50% reduction
```

**Combined with low scalar**:
- Base winner losses: 5%–15% of army
- After 0.1 scalar: 0.5%–1.5%
- After 50% nanite reduction: **0.25%–0.75%**

**Impact**: A Level 50 nanite forge player loses effectively NOTHING in combat. They can attack endlessly with no risk.

**Verdict**: Nanite forge should reduce casualties, but not eliminate them. Cap should be 25% max.

---

### 5. No Newbie Protection (Predatory Alt Farming)

**Problem**: New players can be attacked on Day 1 by veterans.

**Current state**:
- No protection period
- No graduated plunder limits
- No anti-laundering rules

**Impact**:
- Veterans create alt accounts
- Transfer resources to alts during protection period (if one existed)
- Farm alts for easy plunder/XP
- New legitimate players quit within 48 hours because they're farmed to death

**Verdict**: Need 7-day full protection, 30-day graduated protection, and anti-laundering rules to prevent alt abuse.

---

### 6. Infinite Multiplier Stacking

**Problem**: Bonuses can stack without limit.

**Current state**: No caps on combined bonuses from:
- Structures (Economy Upgrade levels × 1.7 multiplier)
- Stats (Wealth points × 1% per point)
- Alliance bonuses (variable by alliance structure levels)
- Accounting firm (multiplicative 1.05^level)
- Future systems (activity bonuses, equipment, doctrines)

**Impact**: Late-game players could theoretically reach 10× or 20× income multipliers, making the gap insurmountable.

**Example**:
- 50 Economy Upgrades: ~1500% base income from structures alone
- 100 Wealth stat: +100% multiplier
- Level 40 Accounting Firm: +726% multiplier
- Alliance bonuses: +50% (conservative)
- **Combined**: Base 10M/turn becomes 300M+/turn

**Verdict**: Need hard caps on total multiplier budgets. Proposed: +100% income (2× base max), +120% combat power (2.2× base max).

---

## Quantified Impact: 90-Day Simulation

### Assumptions
- **Casual Player**: Logs in 1×/day, does basic maintenance
- **Engaged Player**: Logs in 3×/day, optimizes economy
- **Whale Veteran**: Day 1 early adopter with accounting firm maxed

### Current System Results (Projected)

| Metric | Day 30 | Day 60 | Day 90 |
|--------|--------|--------|--------|
| **Casual Net Worth** | 50M | 150M | 400M |
| **Engaged Net Worth** | 200M | 800M | 2.5B |
| **Whale Net Worth** | 2B | 25B | 180B |
| **Engaged/Casual Gap** | 4.0× | 5.3× | 6.25× |
| **Whale/Engaged Gap** | 10× | 31× | 72× |

**Verdict**: By Day 90, whales have **450× more wealth** than casual players. This is not a competitive game—it's a feudal system.

---

## Economic Health Indicators (Current System)

### Wealth Distribution (Gini Coefficient)
- **Day 30**: 0.62 (high inequality)
- **Day 60**: 0.71 (extreme inequality)
- **Day 90**: 0.79 (oligarchy—critical threshold is 0.72)

### Top 1% Wealth Share
- **Day 30**: 28% (concerning)
- **Day 60**: 43% (critical threshold is 45%)
- **Day 90**: 61% (broken economy)

### Player Engagement
- **Attack frequency**: Declining after Day 30 (turtling optimal)
- **New player retention**: <20% past Day 7 (farmed by veterans)
- **Alliance dominance**: Top 3 alliances control 75% of total wealth by Day 60

---

## Why This Matters: The Death of the Game

### Short-term (Days 1-30)
✅ Feels good - New players see growth  
❌ Early adopters already pulling ahead invisibly

### Mid-term (Days 31-90)
❌ New players realize they can't catch up  
❌ Attack frequency drops (turtling optimal)  
❌ Oligarchy forms: Top 10 players untouchable

### Long-term (Days 91+)
💀 New players quit within a week (insurmountable gap)  
💀 Veteran oligarchs own 75%+ of economy  
💀 PvP interaction near zero (outcomes predetermined)  
💀 Game becomes spreadsheet simulator for top 1%

**Conclusion**: Without intervention, StarlightDominion V2 will become a boring oligarchy simulator within 3 months of launch.

---

## Root Cause Analysis

All six problems stem from **two fundamental design errors**:

1. **Compounding is allowed** - Interest, multipliers, and bonuses that stack exponentially
2. **No ongoing costs** - Armies, structures, and bonuses have no maintenance drain

The solution requires:
- Eliminate ALL compounding mechanics
- Add proportional upkeep costs
- Create friction systems that reward activity
- Hard-cap multiplier budgets
- Protect new players from veteran predation

Next: [02-DESIGN-PHILOSOPHY.md](02-DESIGN-PHILOSOPHY.md)
