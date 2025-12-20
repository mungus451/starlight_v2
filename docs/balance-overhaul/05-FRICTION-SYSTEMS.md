---
layout: default
title: Friction Systems
---

# Friction Systems: Maintenance & Deployment Costs

## Overview

**Problem**: In the current system, armies are free forever after initial training. This enables:
- Massive army hoarding with zero economic cost
- Turtling (build army, go inactive) as optimal strategy
- No incentive to optimize force composition

**Solution**: Add ongoing costs proportional to force size and projection.

---

## Unit Maintenance System

### Philosophy

**Principle**: Large standing armies should require economic commitment to sustain.

**Analogy**: A real military has ongoing costs (salaries, supplies, equipment). Same should apply here.

**Balance Goal**: Maintenance should create economic pressure without bankrupting players.

---

### Configuration

```php
'maintenance' => [
    'enabled' => true,
    
    // Newbie buffer: first 2k military units are free
    'free_garrison_units' => 2_000,
    
    // Per-unit maintenance costs (credits per turn)
    'cost_per_unit_per_turn' => [
        'workers'  => 0,      // Civilians are free (they generate income)
        'soldiers' => 3,      // 3 credits/turn
        'guards'   => 4,      // 4 credits/turn
        'spies'    => 50,     // 50 credits/turn (expensive specialists)
        'sentries' => 10,     // 10 credits/turn
    ],
    
    // Safety cap: maintenance cannot exceed 40% of gross income
    'max_maintenance_fraction_of_gross_income' => 0.40,
],
```

---

### How Maintenance Works

#### Example 1: Small Army (Below Free Garrison)

**Profile**:
- 500 soldiers, 300 guards, 50 spies
- Total military: 850 units

**Calculation**:
- Free garrison: 2,000 units
- Billable units: 0 (under free threshold)
- **Maintenance cost**: 0 credits/turn

**Verdict**: Small armies are free (newbie protection).

---

#### Example 2: Medium Army (Above Free Garrison)

**Profile**:
- 10,000 soldiers, 5,000 guards, 500 spies
- Total military: 15,500 units
- Gross income: 5M/turn

**Calculation**:
1. Free garrison: 2,000 units (distributed proportionally)
   - Soldiers: 2000 × (10000/15500) = 1,290 free
   - Guards: 2000 × (5000/15500) = 645 free
   - Spies: 2000 × (500/15500) = 65 free
2. Billable units:
   - Soldiers: 10,000 - 1,290 = 8,710 × 3 = 26,130
   - Guards: 5,000 - 645 = 4,355 × 4 = 17,420
   - Spies: 500 - 65 = 435 × 50 = 21,750
3. **Total maintenance**: 65,300 credits/turn
4. **As fraction of income**: 65,300 / 5M = 1.3% ✅ (under 40% cap)

**Verdict**: Moderate cost, easily affordable.

---

#### Example 3: Massive Army (Approaching Cap)

**Profile**:
- 500,000 soldiers, 200,000 guards, 10,000 spies
- Total military: 710,000 units
- Gross income: 50M/turn

**Calculation**:
1. Free garrison: 2,000 units (negligible)
2. Billable units:
   - Soldiers: ~500,000 × 3 = 1,500,000
   - Guards: ~200,000 × 4 = 800,000
   - Spies: ~10,000 × 50 = 500,000
3. **Total uncapped maintenance**: 2,800,000 credits/turn
4. **Cap check**: 40% of 50M = 20M
5. **Actual maintenance**: min(2.8M, 20M) = 2.8M ✅
6. **As fraction of income**: 2.8M / 50M = 5.6%

**Verdict**: Large armies have noticeable cost but won't bankrupt players.

---

#### Example 4: Turtle with Stagnant Economy

**Profile**:
- 1,000,000 soldiers (hoarded over months)
- Gross income: 10M/turn (low structures)
- Went inactive, army just sitting

**Calculation**:
1. Uncapped maintenance: 1M × 3 = 3M/turn
2. Cap check: 40% of 10M = 4M
3. **Actual maintenance**: 3M/turn
4. **As fraction of income**: 3M / 10M = 30%

**Verdict**: Turtle pays 30% of income just to maintain army. Economy stagnates unless they go active.

---

### Calculation Formula

```php
function calculateMaintenanceCost(User $user): int
{
    $config = config('game_balance.maintenance');
    
    if (!$config['enabled']) {
        return 0;
    }
    
    $freeGarrison = $config['free_garrison_units'];
    $costPerUnit = $config['cost_per_unit_per_turn'];
    
    // Calculate total military units
    $militaryUnits = [
        'soldiers' => $user->soldiers,
        'guards' => $user->guards,
        'spies' => $user->spies,
        'sentries' => $user->sentries,
    ];
    
    $totalMilitary = array_sum($militaryUnits);
    
    // If under free garrison, no cost
    if ($totalMilitary <= $freeGarrison) {
        return 0;
    }
    
    // Distribute free garrison proportionally
    $maintenanceCost = 0;
    foreach ($militaryUnits as $type => $count) {
        $freeForType = (int) ($freeGarrison * ($count / $totalMilitary));
        $billable = max(0, $count - $freeForType);
        $maintenanceCost += $billable * $costPerUnit[$type];
    }
    
    // Apply cap: cannot exceed 40% of gross income
    $grossIncome = $this->calculateGrossIncome($user);
    $maxMaintenance = (int) ($grossIncome * $config['max_maintenance_fraction_of_gross_income']);
    
    return min($maintenanceCost, $maxMaintenance);
}
```

---

### Code Integration

#### File: `/cron/process_turn.php`

```php
// After income calculation
$income = $turnProcessor->calculateIncome($user);

// Calculate maintenance
$maintenance = $turnProcessor->calculateMaintenance($user);

// Apply maintenance (deduct from credits)
$netIncome = $income - $maintenance;
$user->credits = max(0, $user->credits + $netIncome);

// Log maintenance
Log::info("Turn processed", [
    'user_id' => $user->id,
    'gross_income' => $income,
    'maintenance' => $maintenance,
    'net_income' => $netIncome,
]);
```

#### File: `/app/Models/Services/TurnProcessorService.php`

```php
public function calculateMaintenance(User $user): int
{
    $config = config('game_balance.maintenance');
    
    if (!$config['enabled']) {
        return 0;
    }
    
    // ... implementation from formula above
    
    return $maintenanceCost;
}
```

---

## Deployment Costs

### Philosophy

**Problem**: Attacking is "free" except for casualties. No upfront investment required.

**Solution**: Pay credits when projecting force (attacks, spy missions). Not for defending.

**Rationale**: Logistics costs to deploy armies, supply espionage operations, etc.

---

### Configuration

```php
'deployment_costs' => [
    'enabled' => true,
    
    // Attack deployment cost
    'cost_per_1k_offense_power' => 2_500,  // 2.5k per 1k offense power
    
    // Spy mission deployment cost
    'cost_per_1k_spy_power' => 5_000,  // 5k per 1k spy power (more expensive)
    
    // Min/max bounds
    'min_cost' => 10_000,
    'max_cost' => 2_000_000,
    
    // Only applies to offensive actions
    'applies_to_actions' => ['attack', 'spy'],  // NOT defense
],
```

---

### How Deployment Costs Work

#### Example 1: Small Attack

**Attacker Profile**:
- 5,000 soldiers
- 10 offense upgrade levels
- Base offense power per soldier: 1.0
- Bonus from structures: 10 × 0.1 = 1.0× (doubles power)
- **Total offense power**: 5,000 × 2.0 = 10,000

**Deployment Cost Calculation**:
- Power in thousands: 10,000 / 1,000 = 10
- Cost: 10 × 2,500 = 25,000 credits
- **Deployment cost**: 25,000 credits (paid before attack)

**Verdict**: Small attack costs 25k to deploy. Compare to potential plunder.

---

#### Example 2: Large Attack

**Attacker Profile**:
- 200,000 soldiers
- 30 offense upgrade levels
- Total offense power: 200,000 × 4.0 = 800,000

**Deployment Cost Calculation**:
- Power in thousands: 800,000 / 1,000 = 800
- Uncapped cost: 800 × 2,500 = 2,000,000
- Apply max cap: min(2M, 2M) = 2,000,000 credits
- **Deployment cost**: 2M credits (paid before attack)

**Verdict**: Large attack costs 2M to deploy. Must plunder >2M to profit.

---

#### Example 3: Spy Mission

**Spy Profile**:
- 1,000 spies
- 20 spy upgrade levels
- Total spy power: 1,000 × 3.0 = 3,000

**Deployment Cost Calculation**:
- Power in thousands: 3,000 / 1,000 = 3
- Cost: 3 × 5,000 = 15,000 credits
- **Deployment cost**: 15,000 credits (paid before mission)

**Verdict**: Spy missions cost 15k. Intel has a price.

---

### Calculation Formula

```php
function calculateDeploymentCost(User $attacker, string $action): int
{
    $config = config('game_balance.deployment_costs');
    
    if (!$config['enabled'] || !in_array($action, $config['applies_to_actions'])) {
        return 0;
    }
    
    if ($action === 'attack') {
        $power = $this->battleService->calculateOffensePower($attacker);
        $costPer1k = $config['cost_per_1k_offense_power'];
    } elseif ($action === 'spy') {
        $power = $this->spyService->calculateSpyPower($attacker);
        $costPer1k = $config['cost_per_1k_spy_power'];
    } else {
        return 0;
    }
    
    $powerInThousands = $power / 1000;
    $cost = (int) ($powerInThousands * $costPer1k);
    
    // Apply min/max bounds
    $cost = max($config['min_cost'], $cost);
    $cost = min($config['max_cost'], $cost);
    
    return $cost;
}
```

---

### Code Integration

#### File: `/app/Controllers/BattleController.php`

```php
public function attack(Request $request, int $defenderId): Response
{
    $attacker = $this->getAuthenticatedUser();
    
    // Calculate deployment cost
    $deploymentCost = $this->battleService->calculateDeploymentCost($attacker, 'attack');
    
    // Check if attacker can afford it
    if ($attacker->credits < $deploymentCost) {
        $this->session->setFlash('error', "Deployment costs {$deploymentCost} credits. You have {$attacker->credits}.");
        return $this->redirect('/battle');
    }
    
    // Deduct deployment cost BEFORE battle
    $attacker->credits -= $deploymentCost;
    $this->userRepository->update($attacker);
    
    // Process battle
    $result = $this->battleService->processAttack($attacker, $defender);
    
    // Log deployment cost
    Log::info("Attack deployment", [
        'attacker_id' => $attacker->id,
        'deployment_cost' => $deploymentCost,
    ]);
    
    // ... rest of attack resolution
}
```

#### File: `/app/Controllers/SpyController.php`

```php
public function spy(Request $request, int $targetId): Response
{
    $spy = $this->getAuthenticatedUser();
    
    // Calculate deployment cost
    $deploymentCost = $this->spyService->calculateDeploymentCost($spy, 'spy');
    
    // Check if spy can afford it
    if ($spy->credits < $deploymentCost) {
        $this->session->setFlash('error', "Spy mission costs {$deploymentCost} credits.");
        return $this->redirect('/spy');
    }
    
    // Deduct deployment cost BEFORE mission
    $spy->credits -= $deploymentCost;
    $this->userRepository->update($spy);
    
    // Process spy mission
    $result = $this->spyService->processMission($spy, $target);
    
    // ... rest of spy logic
}
```

---

## Combined Impact: Maintenance + Deployment

### Example: Active vs Turtle Player (Day 60)

#### Active Player
- **Army**: 50k soldiers, 20k guards, 1k spies
- **Maintenance**: ~200k/turn
- **Attacks**: 5/day × 50k deployment = 250k/day
- **Income**: 10M/day (structures + combat spoils)
- **Net**: 10M - 200k - 250k = 9.55M/day profit ✅

#### Turtle Player
- **Army**: 500k soldiers (hoarded, inactive)
- **Maintenance**: 2M/turn (capped at 40% of income)
- **Attacks**: 0/day (inactive)
- **Income**: 5M/day (passive structures only)
- **Net**: 5M - 2M = 3M/day profit ❌

**Verdict**: Active player earns 3× more net income despite smaller army. Turtling is punished.

---

## Economic Pressure Dynamics

### Phase 1: Early Game (Days 1-30)
- Small armies (<2k units) → Free maintenance
- Low deployment costs (10-50k per attack)
- **Economic pressure**: Minimal

### Phase 2: Mid Game (Days 31-90)
- Growing armies (10k-50k units) → Moderate maintenance (100k-500k/turn)
- Medium deployment costs (100k-500k per attack)
- **Economic pressure**: Noticeable, but manageable with active play

### Phase 3: Late Game (Days 91+)
- Massive armies (100k+ units) → High maintenance (1M-20M/turn, capped)
- Large deployment costs (500k-2M per attack)
- **Economic pressure**: Forces strategic decisions (trim army? boost economy?)

---

## Counterbalancing Mechanisms

### Why 40% Cap on Maintenance?

**Without cap**: Players with 1M+ armies could pay 90%+ of income to maintenance → bankruptcy spiral

**With cap**: Maintenance maxes at 40% of gross income → Players always have 60% for growth/investment

**Result**: Large armies are expensive but won't destroy your economy.

---

### Why Min/Max Deployment Costs?

**Min (10k)**: Ensures even tiny attacks have a cost (prevents spam)

**Max (2M)**: Prevents ultra-wealthy players from being priced out of attacking

**Result**: Deployment costs scale with power but plateau at reasonable level.

---

## Testing Requirements

### Unit Tests

```php
class MaintenanceTest extends TestCase
{
    public function testSmallArmyNoMaintenance()
    {
        $user = $this->createUser(['soldiers' => 1000, 'guards' => 500]);
        $cost = $this->turnProcessor->calculateMaintenance($user);
        $this->assertEquals(0, $cost);
    }
    
    public function testMediumArmyProportionalMaintenance()
    {
        $user = $this->createUser(['soldiers' => 10000, 'guards' => 5000, 'spies' => 500]);
        $cost = $this->turnProcessor->calculateMaintenance($user);
        $this->assertGreaterThan(0, $cost);
        $this->assertLessThan(100000, $cost);  // Reasonable range
    }
    
    public function testMaintenanceCappedAt40Percent()
    {
        $user = $this->createUser(['soldiers' => 1000000, 'income' => 1000000]);
        $cost = $this->turnProcessor->calculateMaintenance($user);
        $this->assertLessThanOrEqual(400000, $cost);  // 40% cap
    }
}

class DeploymentCostTest extends TestCase
{
    public function testSmallAttackLowCost()
    {
        $attacker = $this->createUser(['soldiers' => 1000]);
        $cost = $this->battleService->calculateDeploymentCost($attacker, 'attack');
        $this->assertGreaterThanOrEqual(10000, $cost);  // Min cost
    }
    
    public function testLargeAttackCappedCost()
    {
        $attacker = $this->createUser(['soldiers' => 1000000]);
        $cost = $this->battleService->calculateDeploymentCost($attacker, 'attack');
        $this->assertLessThanOrEqual(2000000, $cost);  // Max cost
    }
    
    public function testDefenseHasNoDeploymentCost()
    {
        $defender = $this->createUser(['guards' => 100000]);
        $cost = $this->battleService->calculateDeploymentCost($defender, 'defense');
        $this->assertEquals(0, $cost);  // Defense is free
    }
}
```

---

Next: [06-ENGAGEMENT-SYSTEMS.md](06-ENGAGEMENT-SYSTEMS.md)
