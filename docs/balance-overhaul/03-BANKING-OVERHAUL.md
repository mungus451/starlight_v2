---
layout: default
title: Banking Overhaul
---

# Banking Overhaul: From Interest to Insurance

## Problem Recap

**Current System**: Triple compounding creates exponential wealth gap
1. Player bank interest: 0.03%/turn (8.64%/day compounded)
2. Alliance treasury interest: 0.5%/turn (144%/day compounded)
3. Accounting firm multiplier: 1.05^level (11.5× income at level 50)

**Impact**: Day 1 early adopters become oligarchs by Day 90, new players never catch up.

---

## Solution: Insurance Model (Lane A)

### Philosophy Shift

**From**: "Banking makes you richer"  
**To**: "Banking makes you safer"

Players don't earn interest on banked credits. Instead, banking provides:
- Rebuild cost reduction on defeat
- Repair discounts for structures
- Vault protection against plunder

---

## Configuration Changes

### 1. Kill Player Bank Interest

```php
'turn_processor' => [
    'bank_interest_rate' => 0.0,  // was 0.0003
]
```

**Rationale**: No passive wealth growth. Your wealth grows from structures, workers, and combat—not from sitting on gold.

**Impact**:
- 100M banked at Day 0 is still 100M at Day 90 (minus any spending)
- No exponential compounding
- Early adopters can't "bank and forget"

---

### 2. Kill Alliance Treasury Interest

```php
'alliance_treasury' => [
    'bank_interest_rate' => 0.0,  // was 0.005
]
```

**Rationale**: Alliance treasuries should grow from member contributions (taxes, tributes), not from passive compounding.

**Impact**:
- Alliance war chests remain static unless members contribute
- No "build treasury, watch it explode" meta
- Forces alliances to remain active to fund operations

---

### 3. Fix Accounting Firm Compounding

**Current (Broken)**:
```php
'accounting_firm_multiplier' => 1.05,  // 5% multiplicative per level
```
**Impact**: Level 50 = 1.05^50 = 11.47× income (1,047% boost)

**Proposed (Fixed)**:
```php
'turn_processor' => [
    'accounting_firm_bonus_per_level' => 0.01,  // 1% additive per level
    'accounting_firm_soft_cap' => 25,           // DR kicks in after 25
    'accounting_firm_bonus_after_soft_cap' => 0.005,  // 0.5% after 25
    'accounting_firm_hard_cap' => 50,           // No benefit past 50
]
```

**Impact**: 
- Level 10: +10% (was +63%)
- Level 25: +25% (was +238%)
- Level 30: +27.5% (was +332%)
- Level 50: +40% (was +1,047%)

**Calculation**:
```
Bonus = min(level, 25) × 0.01 + max(0, level - 25) × 0.005
Capped at level 50
```

**Code Implementation**:
```php
// In turn processor service
public function calculateAccountingFirmBonus(int $level): float {
    $softCap = config('game_balance.turn_processor.accounting_firm_soft_cap');
    $hardCap = config('game_balance.turn_processor.accounting_firm_hard_cap');
    $baseRate = config('game_balance.turn_processor.accounting_firm_bonus_per_level');
    $diminishedRate = config('game_balance.turn_processor.accounting_firm_bonus_after_soft_cap');
    
    if ($level > $hardCap) {
        $level = $hardCap;
    }
    
    $bonus = min($level, $softCap) * $baseRate;
    
    if ($level > $softCap) {
        $bonus += ($level - $softCap) * $diminishedRate;
    }
    
    return $bonus;
}

// Usage in income calculation
$baseIncome = $econLevels * 100000 + $workers * 100;
$accountingBonus = $this->calculateAccountingFirmBonus($user->accounting_firm_level);
$wealthBonus = $user->wealth * 0.01;

// ADDITIVE, not multiplicative
$totalIncome = $baseIncome * (1 + $accountingBonus + $wealthBonus);
```

---

## Insurance Model Benefits

### Banking Perks Configuration

```php
'banking' => [
    'mode' => 'insurance',  // replaces interest model
    'perks' => [
        'rebuild_cost_reduction_on_defeat' => 0.20,  // 20% cheaper retraining
        'repair_discount' => 0.10,  // 10% cheaper structure repairs
        'vault_protection_multiplier' => 1.0,  // enables vault tiers
    ],
]
```

### How It Works

#### Perk 1: Rebuild Cost Reduction
When a player loses a battle and suffers casualties:
- Normal retrain cost: Dead soldiers × 15,000 credits
- With banking: Dead soldiers × 12,000 credits (20% off)

**Example**: Lose 10k soldiers
- Without banking: 150M to retrain
- With banking: 120M to retrain
- **Savings**: 30M

**Code Hook**: In battle resolution service, apply discount when calculating rebuild costs.

#### Perk 2: Repair Discount
When a player repairs damaged structures:
- Normal repair cost: (Damage level) × (base cost) × (multiplier)
- With banking: Same formula × 0.90 (10% off)

**Code Hook**: In structure service, check if player has banked credits, apply 10% discount.

#### Perk 3: Vault Protection
Enables graduated protection against plunder (see next section).

---

## Why Insurance Model Works

### Economic Incentive Alignment

**Current System**:
- Optimal: Bank everything, maximize interest
- Suboptimal: Spend on structures/armies

**Proposed System**:
- Optimal: Maintain emergency fund, invest rest in structures/armies
- Suboptimal: Hoard everything (no growth, just safety)

**Result**: Players are incentivized to **circulate wealth** through economy (building, training, attacking) rather than hoarding.

---

## Migration Impact

### For Existing Players

**Before Migration**:
- Player A: 10B banked, earning 8.64M/day from interest
- Player B: 1B banked, earning 864k/day from interest

**After Migration**:
- Player A: 10B banked, earning ZERO from interest, but has 20% rebuild discount and vault protection
- Player B: 1B banked, earning ZERO from interest, same perks

**Perception Management**:
- Frame as "banking now provides insurance, not passive income"
- Highlight new perks (rebuild discounts, vault protection)
- Show that active players (structures, combat) now have advantage over turtles

---

## Comparison: Three Banking Models

### Lane A: Insurance (Proposed)
- **Interest**: ZERO
- **Perks**: Rebuild discount, vault protection
- **Philosophy**: Safety, not growth

### Lane B: Trivial Interest (Alternative)
- **Interest**: 0.001%/turn (0.288%/day)
- **Perks**: None
- **Philosophy**: Nominal reward, prevents zero feel

### Lane C: Flat Stipend (Alternative)
- **Interest**: ZERO
- **Stipend**: 100k/day for all banked amounts
- **Philosophy**: Wealth-blind reward

**Decision**: Lane A (Insurance) chosen because:
1. Cleanest break from compounding
2. Most transparent (no hidden growth)
3. Aligns incentives toward active play
4. Easiest to communicate: "Banks don't print money"

---

## Testing Requirements

### Pre-Launch Simulation

Run 90-day simulation with:
- **Archetype A**: Casual (bank-heavy, low structures)
- **Archetype B**: Engaged (balanced bank/structures)
- **Archetype C**: Aggressive (minimal bank, max structures)

**Expected Results (Lane A)**:
- Day 30: B > A > C (engaged pulling ahead)
- Day 60: B ≈ C > A (active strategies dominate)
- Day 90: C > B > A (aggression pays off)

**KPI Validation**:
- Wealth Gini <0.65 at Day 90
- Top 1% holds <35% of wealth
- Power gap engaged/casual ≤2.0×

---

## Communication Plan

### Player Announcement Template

**Title**: "Banking Overhaul: Interest → Insurance"

**Body**:
> Starting [DATE], banking in StarlightDominion V2 will shift from an interest-based model to an insurance model.
> 
> **What's Changing**:
> - Bank accounts will NO LONGER earn passive interest
> - Alliance treasuries will NO LONGER earn passive interest
> - Accounting Firm will provide additive bonuses (not multiplicative)
> 
> **What You Get Instead**:
> - 20% cheaper rebuild costs when you lose battles
> - 10% discount on structure repairs
> - Graduated vault protection against plunder
> 
> **Why**:
> Passive interest was creating an exponential wealth gap where early adopters became unbeatable oligarchs. The new system rewards **active play** (building, combat, espionage) over **passive hoarding**.
> 
> **Your Wealth Is Safe**:
> All existing banked credits remain yours. Nothing is being taken away—interest is simply being turned off going forward.
> 
> **Migration Timeline**:
> - [DATE-7]: Announcement
> - [DATE-1]: Final warning
> - [DATE]: Change goes live
> - [DATE+7]: Balance review

---

## Code Changes Required

### Files to Modify

1. **`/cron/process_turn.php`**
   - Remove bank interest calculation for users
   - Change accounting firm from multiplicative to additive function
   - Add vault protection tier calculation

2. **`/app/Models/Services/TurnProcessorService.php`**
   - Modify `calculatePlayerIncome()` method
   - Add `calculateAccountingFirmBonus()` method (additive with DR)
   - Remove interest accrual logic

3. **`/app/Models/Services/AllianceTreasuryService.php`**
   - Remove treasury interest calculation
   - Keep tax collection logic intact

4. **`/app/Models/Services/BattleService.php`**
   - Add rebuild cost discount check (20% if player has banked credits)
   - Hook into casualty resolution

5. **`/app/Models/Services/StructureService.php`**
   - Add repair cost discount check (10% if player has banked credits)

### Testing Checklist

- [ ] Unit test: `calculateAccountingFirmBonus()` returns correct values
- [ ] Unit test: Bank interest = 0 for all users
- [ ] Unit test: Alliance treasury interest = 0
- [ ] Integration test: Turn processor applies additive accounting bonus
- [ ] Integration test: Rebuild costs apply 20% discount correctly
- [ ] Integration test: Repair costs apply 10% discount correctly
- [ ] Simulation test: 90-day run validates KPIs

---

## Rollback Plan

If insurance model proves unpopular or flawed:

### Option 1: Revert to Trivial Interest (Lane B)
- Set `bank_interest_rate = 0.00001` (0.001%/turn)
- Keep insurance perks as added benefit
- **Rationale**: Symbolic interest to prevent "zero feel" complaints

### Option 2: Add Flat Stipend (Lane C)
- Daily stipend of 100k for anyone with >1M banked
- **Rationale**: Reward having emergency fund, but not proportional to size

### Option 3: Full Revert
- Restore original interest rates
- Accept oligarchy outcome
- **Rationale**: Only if player base overwhelmingly rejects changes

---

Next: [04-PROTECTION-SYSTEMS.md](04-PROTECTION-SYSTEMS.md)
