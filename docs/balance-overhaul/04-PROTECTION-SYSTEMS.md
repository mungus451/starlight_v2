---
layout: default
title: Protection Systems
---

# Protection Systems: Vault & Newbie Shields

## Overview

Two complementary protection systems:
1. **Vault Protection**: Graduated plunder limits based on account age
2. **Newbie Protection**: Full/partial immunity for first 7/30 days

**Goal**: Protect new players from predatory farming while preventing alt abuse.

---

## Vault Protection Tiers

### Philosophy

**Problem**: Veterans can be farmed endlessly for 10% of their bank per attack.

**Solution**: Graduated protection that:
- Fully protects newbies (<7 days)
- Mostly protects growing players (<30 days)
- Partially protects veterans (30+ days)

**Anti-Exploit**: Cap absolute amounts to prevent "bank 10B in day 6, become untouchable."

---

### Configuration

```php
'vault_protection' => [
    'enabled' => true,
    'tiers' => [
        // Tier 1: New players (Days 0-7)
        [
            'max_account_age_days' => 7,
            'protected_percent' => 1.00,      // 100% protected
            'protected_cap' => 2_000_000,     // Max 2M protected
        ],
        
        // Tier 2: Growing players (Days 8-30)
        [
            'max_account_age_days' => 30,
            'protected_percent' => 0.90,      // 90% protected
            'protected_cap' => 20_000_000,    // Max 20M protected
        ],
        
        // Tier 3: Veterans (Days 31+)
        [
            'max_account_age_days' => PHP_INT_MAX,
            'protected_percent' => 0.50,      // 50% protected
            'protected_cap' => 200_000_000,   // Max 200M protected
        ],
    ],
    // Note: Overage beyond protected_cap is fully raidable
],
```

---

### How Vault Protection Works

#### Example 1: Day 5 Newbie

**Profile**:
- Account age: 5 days
- Bank balance: 1M credits
- Tier: 1 (100% protected, 2M cap)

**Attack Outcome**:
- Normal plunder: 10% × 1M = 100k
- Vault protection: 100% × min(1M, 2M) = 1M protected
- **Actual plunder**: 0 credits

**Verdict**: Newbie is fully protected. Attacker wastes turns.

---

#### Example 2: Day 15 Growing Player

**Profile**:
- Account age: 15 days
- Bank balance: 10M credits
- Tier: 2 (90% protected, 20M cap)

**Attack Outcome**:
- Normal plunder: 10% × 10M = 1M
- Vault protection: 90% × min(10M, 20M) = 9M protected
- Exposed: 1M unprotected
- **Actual plunder**: 10% × 1M = 100k

**Verdict**: Player loses 10% of their exposed assets, not 10% of total.

---

#### Example 3: Day 60 Veteran (Moderate Wealth)

**Profile**:
- Account age: 60 days
- Bank balance: 150M credits
- Tier: 3 (50% protected, 200M cap)

**Attack Outcome**:
- Normal plunder: 10% × 150M = 15M
- Vault protection: 50% × min(150M, 200M) = 75M protected
- Exposed: 75M unprotected
- **Actual plunder**: 10% × 75M = 7.5M

**Verdict**: Veteran loses 7.5M instead of 15M. Still worth attacking.

---

#### Example 4: Day 120 Whale (Massive Wealth)

**Profile**:
- Account age: 120 days
- Bank balance: 5B credits
- Tier: 3 (50% protected, 200M cap)

**Attack Outcome**:
- Normal plunder: 10% × 5B = 500M
- Vault protection: 50% × min(5B, 200M) = 100M protected (CAP HIT)
- Exposed: 4.9B unprotected
- **Actual plunder**: 10% × 4.9B = 490M

**Verdict**: Whale is HIGHLY raidable. Hoarding 5B is risky—should invest in structures/armies.

---

### Calculation Formula

```php
function calculatePlunderAmount(User $defender, float $basePlunderRate): int
{
    $vaultTiers = config('game_balance.vault_protection.tiers');
    $accountAgeDays = now()->diffInDays($defender->created_at);
    $bankBalance = $defender->credits;
    
    // Find applicable tier
    $tier = collect($vaultTiers)->first(function($t) use ($accountAgeDays) {
        return $accountAgeDays <= $t['max_account_age_days'];
    });
    
    // Calculate protected amount (with cap)
    $protectedAmount = min(
        $bankBalance * $tier['protected_percent'],
        $tier['protected_cap']
    );
    
    // Exposed amount is remainder
    $exposedAmount = max(0, $bankBalance - $protectedAmount);
    
    // Plunder is basePlunderRate of exposed amount
    return (int) ($exposedAmount * $basePlunderRate);
}
```

---

### Code Integration

#### File: `/app/Models/Services/BattleService.php`

```php
public function resolvePlunder(User $attacker, User $defender): int
{
    $basePlunderRate = config('game_balance.attack.plunder_percent');
    
    // Check if vault protection is enabled
    if (!config('game_balance.vault_protection.enabled')) {
        // Old behavior: plunder entire bank
        return (int) ($defender->credits * $basePlunderRate);
    }
    
    // New behavior: plunder only exposed amount
    $plunderAmount = $this->calculateVaultProtectedPlunder($defender, $basePlunderRate);
    
    // Log the plunder event
    Log::info("Plunder calculated", [
        'defender_id' => $defender->id,
        'bank_balance' => $defender->credits,
        'plunder_amount' => $plunderAmount,
        'account_age_days' => now()->diffInDays($defender->created_at),
    ]);
    
    return $plunderAmount;
}

private function calculateVaultProtectedPlunder(User $defender, float $basePlunderRate): int
{
    $vaultTiers = config('game_balance.vault_protection.tiers');
    $accountAgeDays = now()->diffInDays($defender->created_at);
    $bankBalance = $defender->credits;
    
    // Find applicable tier
    $tier = collect($vaultTiers)->first(function($t) use ($accountAgeDays) {
        return $accountAgeDays <= $t['max_account_age_days'];
    });
    
    // Calculate protected amount (with cap)
    $protectedAmount = min(
        $bankBalance * $tier['protected_percent'],
        $tier['protected_cap']
    );
    
    // Exposed amount is remainder
    $exposedAmount = max(0, $bankBalance - $protectedAmount);
    
    // Plunder is basePlunderRate of exposed amount
    return (int) ($exposedAmount * $basePlunderRate);
}
```

---

## Newbie Protection

### Philosophy

**Problem**: Day 1 players can be attacked by Day 60 veterans with 500k armies.

**Solution**: 
- **Full protection** (Days 0-7): Cannot be attacked at all
- **Partial protection** (Days 8-30): Can be attacked, but reduced plunder
- **Early exit conditions**: Aggressive play or rapid growth ends protection

**Anti-Exploit**: Cannot send attacks/donations while protected (prevents alt farming).

---

### Configuration

```php
'newbie_protection' => [
    'enabled' => true,
    
    // Full immunity period
    'days_full_protection' => 7,  // Cannot be attacked for first 7 days
    
    // Partial protection period
    'days_partial_protection_end' => 30,  // Reduced plunder through day 30
    'plunder_multiplier_partial' => 0.25,  // Only 25% of normal plunder
    
    // Protection against power disparity
    'min_attacker_to_defender_power_ratio' => 2.0,  // Attacker must be <2× defender power
    
    // Early exit conditions (ANY triggers end protection)
    'ends_when' => [
        'age_days' => 30,
        'power_score' => 25_000,  // If player grows strong fast
        'total_attacks_sent' => 10,  // If player attacks 10 times
    ],
],
```

---

### How Newbie Protection Works

#### Phase 1: Full Protection (Days 0-7)

**Restrictions**:
- ✅ Can be scouted (intel gathering allowed)
- ❌ Cannot be attacked (attack button disabled/fails)
- ❌ Cannot send attacks (prevents abuse)
- ❌ Cannot send donations/transfers (prevents alt farming)

**UI Indicator**: Shield icon next to player name, tooltip: "Protected until [DATE]"

**Early Exit**: If player sends 10 attacks OR reaches 25k power before Day 7, protection ends immediately.

---

#### Phase 2: Partial Protection (Days 8-30)

**Restrictions**:
- ✅ Can be attacked (but reduced plunder)
- ✅ Can send attacks (no restrictions)
- ⚠️ Plunder reduced to 25% (attacker gets 2.5% instead of 10%)
- ⚠️ Power ratio check: attacker must be <2× defender power

**Example**:
- Defender: 30M bank, 5k power
- Attacker A: 100k power (20× stronger) → **Attack blocked** (too strong)
- Attacker B: 8k power (1.6× stronger) → **Attack allowed** → Plunders 2.5% instead of 10%

**Early Exit**: Same conditions as Phase 1.

---

#### Phase 3: No Protection (Days 31+)

**Restrictions**: None. Normal combat rules apply.

---

### Calculation Example: Partial Protection

**Defender Profile**:
- Account age: 20 days (Phase 2)
- Bank balance: 50M credits
- Power score: 10k
- Vault tier: 2 (90% protected, 20M cap)

**Attacker Profile**:
- Power score: 18k (1.8× defender power)

**Plunder Calculation**:
1. Check power ratio: 18k / 10k = 1.8× → ALLOWED (< 2.0×)
2. Calculate exposed amount:
   - Protected: 90% × min(50M, 20M) = 18M
   - Exposed: 50M - 18M = 32M
3. Apply plunder rate:
   - Normal: 10% × 32M = 3.2M
   - Partial protection: 25% × 3.2M = **800k**

**Verdict**: Attacker gets 800k instead of 3.2M. Still worth it, but less profitable.

---

### Code Integration

#### File: `/app/Controllers/BattleController.php`

```php
public function attack(Request $request, int $defenderId): Response
{
    $attacker = $this->getAuthenticatedUser();
    $defender = $this->userRepository->findById($defenderId);
    
    // Check newbie protection
    $protectionStatus = $this->checkNewbieProtection($defender, $attacker);
    
    if ($protectionStatus['blocked']) {
        $this->session->setFlash('error', $protectionStatus['reason']);
        return $this->redirect('/battle');
    }
    
    // Process attack
    $result = $this->battleService->processAttack($attacker, $defender);
    
    // Apply partial protection multiplier if applicable
    if ($protectionStatus['partial']) {
        $result['plunder'] *= config('game_balance.newbie_protection.plunder_multiplier_partial');
    }
    
    // ... rest of attack resolution
}

private function checkNewbieProtection(User $defender, User $attacker): array
{
    $config = config('game_balance.newbie_protection');
    
    if (!$config['enabled']) {
        return ['blocked' => false, 'partial' => false];
    }
    
    $accountAgeDays = now()->diffInDays($defender->created_at);
    
    // Check early exit conditions
    if ($this->hasExitedProtection($defender)) {
        return ['blocked' => false, 'partial' => false];
    }
    
    // Phase 1: Full protection
    if ($accountAgeDays < $config['days_full_protection']) {
        return [
            'blocked' => true,
            'reason' => "Player is protected until " . $defender->created_at->addDays(7)->format('M d'),
        ];
    }
    
    // Phase 2: Partial protection
    if ($accountAgeDays < $config['days_partial_protection_end']) {
        // Check power ratio
        $attackerPower = $this->battleService->calculateTotalPower($attacker);
        $defenderPower = $this->battleService->calculateTotalPower($defender);
        $ratio = $defenderPower > 0 ? $attackerPower / $defenderPower : PHP_FLOAT_MAX;
        
        if ($ratio > $config['min_attacker_to_defender_power_ratio']) {
            return [
                'blocked' => true,
                'reason' => "Player is protected from attacks by opponents {$ratio}× stronger (max 2×).",
            ];
        }
        
        return ['blocked' => false, 'partial' => true];
    }
    
    // Phase 3: No protection
    return ['blocked' => false, 'partial' => false];
}

private function hasExitedProtection(User $user): bool
{
    $config = config('game_balance.newbie_protection.ends_when');
    
    // Check age
    if (now()->diffInDays($user->created_at) >= $config['age_days']) {
        return true;
    }
    
    // Check power score
    $power = $this->battleService->calculateTotalPower($user);
    if ($power >= $config['power_score']) {
        return true;
    }
    
    // Check total attacks sent
    if ($user->total_attacks_sent >= $config['total_attacks_sent']) {
        return true;
    }
    
    return false;
}
```

---

## Anti-Laundering Rules

### Configuration

```php
'anti_laundering' => [
    // Core rules
    'protected_accounts_can_send' => false,  // No outbound transfers during protection
    'protected_accounts_can_receive' => true,  // Can receive help
    
    // Alliance restrictions
    'max_alliance_donation_per_day_protected' => 100_000,  // Symbolic only
    'max_outgoing_transfers_per_day_protected' => 0,
    'alliance_withdrawal_requires_contribution_ledger' => true,
    'cooldown_after_leaving_alliance_hours' => 72,
    
    // Technical safeguards (if feasible)
    'shared_ip_transfer_block' => true,
    
    // Trade restrictions
    'min_unique_trade_partners_for_full_credit' => 3,
],
```

### Enforcement

#### Rule 1: No Outbound Transfers During Protection

**Code Hook**: In `/app/Controllers/BankController.php`

```php
public function transfer(Request $request): Response
{
    $sender = $this->getAuthenticatedUser();
    
    // Check if sender is protected
    if ($this->isNewbieProtected($sender)) {
        $this->session->setFlash('error', 'Cannot send credits while under newbie protection (prevents alt farming).');
        return $this->redirect('/bank');
    }
    
    // ... rest of transfer logic
}
```

#### Rule 2: Alliance Donation Cap

**Code Hook**: In `/app/Controllers/AllianceFundingController.php`

```php
public function donate(Request $request): Response
{
    $donor = $this->getAuthenticatedUser();
    $amount = (int) $request->post('amount');
    
    // Check if donor is protected
    if ($this->isNewbieProtected($donor)) {
        $maxDaily = config('game_balance.anti_laundering.max_alliance_donation_per_day_protected');
        $todayDonations = $this->getTodayDonations($donor->id);
        
        if ($todayDonations + $amount > $maxDaily) {
            $this->session->setFlash('error', "Protected accounts can only donate {$maxDaily} credits/day.");
            return $this->redirect('/alliance/funding');
        }
    }
    
    // ... rest of donation logic
}
```

---

## Testing Requirements

### Unit Tests

```php
class VaultProtectionTest extends TestCase
{
    public function testDay5NewbieFullyProtected()
    {
        $defender = $this->createUser(['created_at' => now()->subDays(5), 'credits' => 1_000_000]);
        $plunder = $this->battleService->calculateVaultProtectedPlunder($defender, 0.10);
        $this->assertEquals(0, $plunder);  // Fully protected
    }
    
    public function testDay15GrowingPlayerPartiallyProtected()
    {
        $defender = $this->createUser(['created_at' => now()->subDays(15), 'credits' => 10_000_000]);
        $plunder = $this->battleService->calculateVaultProtectedPlunder($defender, 0.10);
        $this->assertEquals(100_000, $plunder);  // 10% of 1M exposed
    }
    
    public function testDay120WhaleMostlyExposed()
    {
        $defender = $this->createUser(['created_at' => now()->subDays(120), 'credits' => 5_000_000_000]);
        $plunder = $this->battleService->calculateVaultProtectedPlunder($defender, 0.10);
        $this->assertEquals(490_000_000, $plunder);  // 10% of 4.9B exposed
    }
    
    public function testNewbieProtectionBlocksAttackDay3()
    {
        $defender = $this->createUser(['created_at' => now()->subDays(3)]);
        $attacker = $this->createUser(['created_at' => now()->subDays(60)]);
        $status = $this->battleController->checkNewbieProtection($defender, $attacker);
        $this->assertTrue($status['blocked']);
    }
    
    public function testPartialProtectionReducesPlunderDay20()
    {
        $defender = $this->createUser(['created_at' => now()->subDays(20)]);
        $attacker = $this->createUser(['created_at' => now()->subDays(60)]);
        $status = $this->battleController->checkNewbieProtection($defender, $attacker);
        $this->assertFalse($status['blocked']);
        $this->assertTrue($status['partial']);
    }
}
```

---

Next: [05-FRICTION-SYSTEMS.md](05-FRICTION-SYSTEMS.md)
