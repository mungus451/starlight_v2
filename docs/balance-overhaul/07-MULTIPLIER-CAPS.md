---
layout: default
title: Multiplier Caps
---

# Multiplier Caps: Preventing Runaway Scaling

## Problem

**Current System**: Bonuses can stack infinitely
- Economy 50 levels: ~1500% base income
- Wealth 100 stat: +100%
- Accounting 40 levels: +726%
- Alliance bonuses: +50%
- **Total**: Base 10M → 300M+ (30× multiplier)

**Impact**: Late-game players become gods, newbies never catch up.

---

## Solution: Multiplier Budgets

### Configuration

```php
'multiplier_budgets' => [
    'income' => [
        'structures_max' => 0.50,  // +50% from structures
        'alliance_max' => 0.30,    // +30% from alliance
        'stats_max' => 0.20,       // +20% from stats
        'activity_max' => 0.15,    // +15% from weekly bonus
        'total_max' => 1.00,       // +100% absolute ceiling (2× base)
    ],
    'combat_power' => [
        'structures_max' => 0.50,
        'equipment_max' => 0.40,
        'stats_max' => 0.30,
        'total_max' => 1.20,       // +120% (2.2× base)
    ],
],
```

---

## How Clamping Works

### Income Multiplier Calculation

```php
function calculateIncomeMultipliers(User $user): array
{
    $budgets = config('game_balance.multiplier_budgets.income');
    
    // Calculate raw bonuses
    $structureBonus = $this->calculateStructureBonus($user);
    $allianceBonus = $this->calculateAllianceBonus($user);
    $statBonus = $user->wealth * 0.01;
    $activityBonus = $this->calculateActivityBonus($user);
    
    // Clamp each category
    $structureBonus = min($structureBonus, $budgets['structures_max']);
    $allianceBonus = min($allianceBonus, $budgets['alliance_max']);
    $statBonus = min($statBonus, $budgets['stats_max']);
    $activityBonus = min($activityBonus, $budgets['activity_max']);
    
    // Calculate total
    $totalBonus = $structureBonus + $allianceBonus + $statBonus + $activityBonus;
    
    // Clamp total to absolute ceiling
    $totalBonus = min($totalBonus, $budgets['total_max']);
    
    return [
        'structure' => $structureBonus,
        'alliance' => $allianceBonus,
        'stat' => $statBonus,
        'activity' => $activityBonus,
        'total' => $totalBonus,
        'final_multiplier' => 1.0 + $totalBonus,
    ];
}

// Usage
$baseIncome = $this->calculateBaseIncome($user);
$multipliers = $this->calculateIncomeMultipliers($user);
$finalIncome = $baseIncome * $multipliers['final_multiplier'];
```

---

## Example: Maxed-Out Veteran

**Profile**:
- Economy 50: Raw bonus +150% (clamped to +50%)
- Alliance: Raw bonus +50% (clamped to +30%)
- Wealth 100: Raw bonus +100% (clamped to +20%)
- Activity: +15% (under cap)
- **Total raw**: +315%
- **Total capped**: +100% (hard ceiling)

**Income Calculation**:
- Base: 50M/turn
- Multiplier: 1.0 + 1.0 = 2.0×
- **Final**: 50M × 2.0 = 100M/turn

**Verdict**: Even maxed-out veteran cannot exceed 2× base income.

---

## Why This Matters

### Without Caps (Current System)
- Day 60 veteran: 20× income multiplier
- Day 60 newbie: 2× income multiplier
- **Gap**: 10× power disparity → newbie never catches up

### With Caps (Proposed System)
- Day 60 veteran: 2× income multiplier (capped)
- Day 60 newbie: 1.5× income multiplier
- **Gap**: 1.33× power disparity → newbie can catch up with activity

---

## Visual Transparency

### UI Display: Multiplier Breakdown

```
Income Multipliers
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Structures:  +50% ███████████ (50/50)
Alliance:    +30% ███████     (30/30)
Stats:       +20% █████       (20/20)
Activity:    +15% ████        (15/15)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TOTAL:       +100% ███████████ (100/100)
Final Income: Base × 2.0 = 100M/turn
```

**Purpose**: Players see exactly where bonuses come from and when caps are hit.

---

## Testing Requirements

```php
class MultiplierBudgetTest extends TestCase
{
    public function testIndividualCategoryClamped()
    {
        $user = $this->createUser([
            'economy_upgrade_level' => 100,  // Would give +300% uncapped
        ]);
        
        $multipliers = $this->turnProcessor->calculateIncomeMultipliers($user);
        $this->assertEquals(0.50, $multipliers['structure']);  // Capped at 50%
    }
    
    public function testTotalCeilingEnforced()
    {
        $user = $this->createUser([
            'economy_upgrade_level' => 50,  // +50%
            'alliance_bonus' => 0.40,       // +40%
            'wealth' => 100,                // +100% raw
            'activity_bonus' => 0.15,       // +15%
        ]);
        
        $multipliers = $this->turnProcessor->calculateIncomeMultipliers($user);
        $this->assertEquals(1.00, $multipliers['total']);  // Total capped at +100%
        $this->assertEquals(2.0, $multipliers['final_multiplier']);  // 1.0 + 1.0
    }
    
    public function testCombatPowerCappedSeparately()
    {
        $user = $this->createUser([
            'offense_upgrade_level' => 100,  // Would give +500% uncapped
        ]);
        
        $multipliers = $this->battleService->calculateCombatMultipliers($user);
        $this->assertLessThanOrEqual(2.20, $multipliers['final_multiplier']);  // +120% cap
    }
}
```

---

Next: [08-TESTING-FRAMEWORK.md](08-TESTING-FRAMEWORK.md)
