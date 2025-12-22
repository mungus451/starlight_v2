---
layout: default
title: Engagement Systems
---

# Engagement Systems: Activity Bonuses & Readiness Decay

## Overview

Two complementary systems to reward activity without creating "calendar hostage" mechanics:
1. **Weekly Activity Bonuses**: Reward engagement with income boosts
2. **Readiness Decay**: Penalize idle mega-armies

---

## Weekly Activity Bonus System

### Configuration

```php
'activity_bonus' => [
    'enabled' => true,
    'window' => 'weekly',  // Resets every 7 days
    'income_bonus_max' => 0.15,  // +15% max bonus
    
    'points_per_action' => [
        'battle_win' => 12,
        'battle_loss' => 8,
        'spy_success' => 8,
        'spy_caught' => 4,
        'structure_upgrade' => 6,
        'alliance_donation' => 2,
    ],
    
    'weekly_points_cap' => 120,  // Prevents no-life grinding
    'income_bonus_per_point' => 0.00125,  // 0.125% per point
    'catch_up_weeks' => 1,  // Can earn 2× next week if missed
    
    'anti_bot' => [
        'diminish_repeats_same_target' => true,
        'min_unique_opponents_for_full_credit' => 5,
    ],
],
```

### How It Works

**Earning Points**:
- Attack player A and win: +12 points
- Attack player B and lose: +8 points
- Upgrade structure: +6 points
- Weekly cap: 120 points max

**Converting to Bonus**:
- 120 points × 0.125% = +15% income bonus
- Applied to base income from structures/workers
- Bonus lasts until next reset (7 days)

**Anti-Grind**:
- Attacking same player repeatedly: diminishing returns after 3rd attack
- Need 5+ unique opponents per week for full credit

---

## Readiness Decay System

### Configuration

```php
'readiness' => [
    'enabled' => true,
    'grace_days_no_decay' => 7,  // No penalty first week idle
    'decay_per_turn_after_grace' => 0.001,  // 0.1% per turn
    'min_readiness' => 0.70,  // Cannot decay below 70%
    'threshold_total_units_for_decay' => 100_000,  // Only affects large armies
    'combat_resets_decay_timer' => true,  // Attack/defend resets grace
],
```

### How It Works

**Scenario**: Player with 500k soldiers goes idle

**Timeline**:
- Days 1-7: No decay (grace period)
- Day 8: Readiness starts decaying 0.1%/turn (28.8% per day)
- Day 10: Readiness at ~70% (capped)
- **Combat power**: 500k × 0.70 = 350k effective

**Reset**: Any attack sent/received resets grace period to Day 0

**Purpose**: Penalize turtling without punishing casual players

---

## Combined Example: Active vs Idle Player

### Active Player (Logs in 2×/day)
- Weekly actions: 8 wins, 5 losses, 10 upgrades = 196 points (capped at 120)
- Activity bonus: +15% income
- Readiness: 100% (constantly resetting grace)
- **Net advantage**: +15% income, full army strength

### Idle Player (Logs in 1×/week)
- Weekly actions: 1 upgrade = 6 points
- Activity bonus: +0.75% income
- Readiness: 70% (decayed after 7d grace)
- **Net disadvantage**: -14.25% income, 30% weaker army

**Gap**: Active player has 1.21× income AND 1.43× combat power

---

## Testing Requirements

```php
class ActivityBonusTest extends TestCase
{
    public function testWeeklyCapEnforced()
    {
        $user = $this->createUser();
        $this->earnActivityPoints($user, 200);  // Exceed cap
        $bonus = $this->turnProcessor->calculateActivityBonus($user);
        $this->assertEquals(0.15, $bonus);  // Capped at 15%
    }
    
    public function testAntiGrindDiminishingReturns()
    {
        $attacker = $this->createUser();
        $defender = $this->createUser();
        
        $this->attack($attacker, $defender);  // +12 points
        $this->attack($attacker, $defender);  // +12 points
        $this->attack($attacker, $defender);  // +12 points
        $this->attack($attacker, $defender);  // +6 points (diminished)
        $this->attack($attacker, $defender);  // +6 points (diminished)
    }
}

class ReadinessDecayTest extends TestCase
{
    public function testNoDecayDuringGracePeriod()
    {
        $user = $this->createUser(['last_combat_at' => now()->subDays(5), 'soldiers' => 100000]);
        $readiness = $this->turnProcessor->calculateReadiness($user);
        $this->assertEquals(1.0, $readiness);
    }
    
    public function testDecayAfterGracePeriod()
    {
        $user = $this->createUser(['last_combat_at' => now()->subDays(10), 'soldiers' => 100000]);
        $readiness = $this->turnProcessor->calculateReadiness($user);
        $this->assertLessThan(1.0, $readiness);
        $this->assertGreaterThanOrEqual(0.70, $readiness);  // Min cap
    }
    
    public function testCombatResetsDecay()
    {
        $user = $this->createUser(['last_combat_at' => now()->subDays(10)]);
        $this->attack($user, $this->createUser());
        $readiness = $this->turnProcessor->calculateReadiness($user);
        $this->assertEquals(1.0, $readiness);  // Reset to full
    }
}
```

---

Next: [07-MULTIPLIER-CAPS.md](07-MULTIPLIER-CAPS.md)
