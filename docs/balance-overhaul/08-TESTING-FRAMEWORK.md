---
layout: default
title: Testing Framework
---

# Testing Framework: Simulations & KPI Monitoring

## Philosophy

**Traditional Approach (Flawed)**:
- Write code → Deploy → Discover imbalance at Day 60 → Too late

**Proposed Approach (Robust)**:
- Write config → Simulate 90 days → Validate KPIs → Deploy with confidence

---

## Simulation Framework Architecture

### Core Components

1. **Archetype Definitions**: Casual, Engaged, Turtle player behaviors
2. **Deterministic Engine**: Turn-by-turn simulation with no randomness
3. **KPI Calculators**: Wealth Gini, power gaps, progression timelines
4. **Assertion System**: Fail fast on threshold violations

---

## Player Archetypes

### Archetype 1: Casual Player
```php
$casual = [
    'logins_per_day' => 1,
    'actions_per_login' => 3,
    'strategy' => [
        'structure_priority' => 0.6,  // 60% income on structures
        'army_priority' => 0.3,       // 30% on army
        'bank_priority' => 0.1,       // 10% saved
    ],
    'combat_frequency' => 0.3,  // 0.3 attacks/day
    'alliance_participation' => 'low',
];
```

### Archetype 2: Engaged Player
```php
$engaged = [
    'logins_per_day' => 3,
    'actions_per_login' => 8,
    'strategy' => [
        'structure_priority' => 0.5,
        'army_priority' => 0.4,
        'bank_priority' => 0.1,
    ],
    'combat_frequency' => 1.5,  // 1.5 attacks/day
    'alliance_participation' => 'high',
];
```

### Archetype 3: Turtle Player
```php
$turtle = [
    'logins_per_day' => 0.5,  // Every other day
    'actions_per_login' => 2,
    'strategy' => [
        'structure_priority' => 0.2,
        'army_priority' => 0.1,
        'bank_priority' => 0.7,  // Hoards everything
    ],
    'combat_frequency' => 0,  // Never attacks
    'alliance_participation' => 'none',
];
```

---

## Simulation Engine Pseudocode

```php
class GameSimulator
{
    public function simulate(int $days, array $archetypes): SimulationResult
    {
        $turnsPerDay = 288;
        $totalTurns = $days * $turnsPerDay;
        
        // Initialize players
        $players = [];
        foreach ($archetypes as $name => $config) {
            $players[$name] = $this->createPlayer($config);
        }
        
        // Run simulation turn by turn
        for ($turn = 0; $turn < $totalTurns; $turn++) {
            foreach ($players as $player) {
                // Process turn income
                $this->processTurnIncome($player);
                
                // Apply maintenance costs
                $this->applyMaintenance($player);
                
                // Player actions (builds, attacks, etc.)
                if ($this->shouldPlayerAct($player, $turn)) {
                    $this->executePlayerStrategy($player);
                }
                
                // Apply readiness decay
                $this->applyReadinessDecay($player);
                
                // Calculate activity bonus
                if ($turn % (7 * $turnsPerDay) === 0) {
                    $this->resetWeeklyActivityBonus($player);
                }
            }
            
            // Snapshot every 30 days
            if ($turn % (30 * $turnsPerDay) === 0) {
                $this->recordSnapshot($players, $turn / $turnsPerDay);
            }
        }
        
        return new SimulationResult($players, $this->snapshots);
    }
}
```

---

## KPI Tripwires

### Configuration

```php
'balance_kpis' => [
    // Wealth inequality
    'wealth_gini_warning' => 0.65,
    'wealth_gini_critical' => 0.72,
    
    // Wealth concentration
    'top_1_percent_wealth_warning' => 0.35,
    'top_1_percent_wealth_critical' => 0.45,
    
    // Power gaps
    'power_gap_30d_engaged_vs_casual_max' => 2.0,
    'power_gap_90d_engaged_vs_casual_max' => 2.5,
    
    // Progression timelines
    'median_days_to_economy_10_target' => 10,
    'median_days_to_level_20_target' => 45,
    
    // Engagement
    'attacks_per_active_player_per_day_min' => 0.3,
    'attacks_per_active_player_per_day_target' => 1.0,
],
```

---

## KPI Calculations

### Wealth Gini Coefficient

```php
function calculateGiniCoefficient(array $players): float
{
    $wealths = array_map(fn($p) => $p->netWorth(), $players);
    sort($wealths);
    
    $n = count($wealths);
    $sum = array_sum($wealths);
    
    $numerator = 0;
    foreach ($wealths as $i => $wealth) {
        $numerator += ($i + 1) * $wealth;
    }
    
    $gini = (2 * $numerator) / ($n * $sum) - ($n + 1) / $n;
    
    return round($gini, 3);
}
```

**Thresholds**:
- Gini < 0.40: Egalitarian (unrealistic for MMO)
- Gini 0.40-0.65: Healthy inequality
- Gini 0.65-0.72: Warning zone
- Gini > 0.72: Oligarchy (critical)

---

### Top 1% Wealth Share

```php
function calculateTop1PercentShare(array $players): float
{
    $wealths = array_map(fn($p) => $p->netWorth(), $players);
    rsort($wealths);
    
    $top1PercentCount = max(1, (int)(count($wealths) * 0.01));
    $top1PercentWealth = array_sum(array_slice($wealths, 0, $top1PercentCount));
    $totalWealth = array_sum($wealths);
    
    return round($top1PercentWealth / $totalWealth, 3);
}
```

**Thresholds**:
- <25%: Balanced
- 25-35%: Healthy
- 35-45%: Warning
- >45%: Critical

---

### Power Gap (Engaged vs Casual)

```php
function calculatePowerGap(Player $engaged, Player $casual): float
{
    $engagedPower = $this->calculateTotalPower($engaged);
    $casualPower = $this->calculateTotalPower($casual);
    
    return round($engagedPower / max(1, $casualPower), 2);
}
```

**Thresholds**:
- Day 30: ≤2.0× (engaged should be ahead, not dominating)
- Day 90: ≤2.5× (gap widens but remains catchable)

---

## Priority Tests

### Test 1: 90-Day Archetype Comparison

```php
public function test90DayArchetypeBalance()
{
    $sim = new GameSimulator(config('game_balance'));
    
    $result = $sim->simulate(90, [
        'casual' => $this->casualArchetype(),
        'engaged' => $this->engagedArchetype(),
        'turtle' => $this->turtleArchetype(),
    ]);
    
    // Assert power gaps
    $powerGap = $result->calculatePowerGap('engaged', 'casual');
    $this->assertLessThanOrEqual(2.5, $powerGap, "Engaged/Casual gap too large at Day 90");
    
    // Assert Gini coefficient
    $gini = $result->calculateGiniCoefficient();
    $this->assertLessThan(0.65, $gini, "Wealth inequality too high");
    
    // Assert top 1% share
    $top1 = $result->calculateTop1PercentShare();
    $this->assertLessThan(0.35, $top1, "Wealth too concentrated");
    
    // Assert turtle is worst strategy
    $turtleNetWorth = $result->getPlayer('turtle')->netWorth();
    $casualNetWorth = $result->getPlayer('casual')->netWorth();
    $this->assertLessThan($casualNetWorth, $turtleNetWorth, "Turtling should be suboptimal");
}
```

### Test 2: Vault Protection Integrity

```php
public function testVaultProtectionNeverExceeded()
{
    $defender = $this->createUser(['created_at' => now()->subDays(5), 'credits' => 1_000_000]);
    
    for ($i = 0; $i < 100; $i++) {
        $plunder = $this->battleService->calculateVaultProtectedPlunder($defender, 0.10);
        $this->assertEquals(0, $plunder, "Day 5 player fully protected");
    }
    
    $defender = $this->createUser(['created_at' => now()->subDays(60), 'credits' => 5_000_000_000]);
    $plunder = $this->battleService->calculateVaultProtectedPlunder($defender, 0.10);
    
    $expectedExposed = 5_000_000_000 - 100_000_000;  // 50% of 200M cap
    $expectedPlunder = $expectedExposed * 0.10;
    $this->assertEquals($expectedPlunder, $plunder);
}
```

### Test 3: Multiplier Budget Enforcement

```php
public function testMultiplierBudgetsNeverExceeded()
{
    $maxedPlayer = $this->createUser([
        'economy_upgrade_level' => 100,
        'accounting_firm_level' => 50,
        'wealth' => 200,
        'alliance_bonus' => 0.50,
    ]);
    
    $multipliers = $this->turnProcessor->calculateIncomeMultipliers($maxedPlayer);
    $this->assertLessThanOrEqual(2.0, $multipliers['final_multiplier'], "Income multiplier exceeded 2×");
    
    $combatMultipliers = $this->battleService->calculateCombatMultipliers($maxedPlayer);
    $this->assertLessThanOrEqual(2.2, $combatMultipliers['final_multiplier'], "Combat multiplier exceeded 2.2×");
}
```

### Test 4: Maintenance Cap at 40%

```php
public function testMaintenanceNeverExceeds40Percent()
{
    $player = $this->createUser(['soldiers' => 1_000_000]);
    
    $grossIncome = $this->turnProcessor->calculateGrossIncome($player);
    $maintenance = $this->turnProcessor->calculateMaintenance($player);
    
    $fraction = $maintenance / $grossIncome;
    $this->assertLessThanOrEqual(0.40, $fraction, "Maintenance exceeded 40% of gross income");
}
```

---

## Monitoring & Alerting

### Real-Time KPI Tracking

```php
// In cron job or daily batch
class BalanceMonitor
{
    public function checkKPIs(): void
    {
        $players = User::all();
        
        $gini = $this->calculateGiniCoefficient($players);
        $top1 = $this->calculateTop1PercentShare($players);
        $powerGaps = $this->calculatePowerGaps($players);
        
        // Check thresholds
        if ($gini > config('game_balance.balance_kpis.wealth_gini_critical')) {
            $this->alert('CRITICAL: Wealth Gini exceeded 0.72', $gini);
        }
        
        if ($top1 > config('game_balance.balance_kpis.top_1_percent_wealth_critical')) {
            $this->alert('CRITICAL: Top 1% holds >45% of wealth', $top1);
        }
        
        // Log metrics
        BalanceMetric::create([
            'date' => now(),
            'gini_coefficient' => $gini,
            'top_1_percent_share' => $top1,
            'power_gap_engaged_casual' => $powerGaps['engaged_casual'],
        ]);
    }
}
```

---

## Expected Simulation Results (Proposed System)

| Metric | Day 30 | Day 60 | Day 90 | Target |
|--------|--------|--------|--------|--------|
| **Gini** | 0.52 | 0.58 | 0.62 | <0.65 ✅ |
| **Top 1%** | 18% | 26% | 32% | <35% ✅ |
| **Engaged/Casual Gap** | 1.6× | 1.9× | 2.3× | ≤2.5× ✅ |
| **Turtle Net Worth** | 30M | 80M | 150M | < Casual ✅ |
| **Attacks/Day (Avg)** | 0.8 | 1.1 | 1.3 | ≥1.0 ✅ |

---

Next: [09-IMPLEMENTATION-PLAN.md](09-IMPLEMENTATION-PLAN.md)
