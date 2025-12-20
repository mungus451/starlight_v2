---
layout: default
title: Implementation Plan
---

# Implementation Plan: Code Changes Required

## Overview

8 service layers require modifications to wire new config sections.

---

## Phase 1: Turn Processor (Core Economic Engine)

### File: `/cron/process_turn.php` & `/app/Models/Services/TurnProcessorService.php`

**Changes Required**:

1. **Skip bank interest calculation**
   ```php
   // OLD (remove)
   $interest = $user->credits * config('game_balance.turn_processor.bank_interest_rate');
   $user->credits += $interest;
   
   // NEW (skip)
   // No interest calculation
   ```

2. **Change accounting firm to additive**
   ```php
   // OLD (remove)
   $multiplier = pow(1.05, $user->accounting_firm_level);
   $income *= $multiplier;
   
   // NEW (implement)
   $accountingBonus = $this->calculateAccountingFirmBonus($user->accounting_firm_level);
   // See 03-BANKING-OVERHAUL.md for formula
   ```

3. **Add maintenance calculation**
   ```php
   $maintenance = $this->calculateMaintenance($user);
   $netIncome = $grossIncome - $maintenance;
   // See 05-FRICTION-SYSTEMS.md for formula
   ```

4. **Apply readiness decay**
   ```php
   if ($this->shouldApplyReadinessDecay($user)) {
       $user->readiness = max(0.70, $user->readiness - 0.001);
   }
   // See 06-ENGAGEMENT-SYSTEMS.md
   ```

5. **Calculate activity bonuses**
   ```php
   $activityBonus = $this->calculateActivityBonus($user);
   // Applied in income multiplier calculation
   // See 06-ENGAGEMENT-SYSTEMS.md
   ```

**Estimated Work**: 4-6 hours

---

## Phase 2: Plunder Logic (Vault Protection)

### File: `/app/Models/Services/BattleService.php`

**Changes Required**:

1. **Implement vault-protected plunder**
   ```php
   public function resolvePlunder(User $attacker, User $defender): int
   {
       $basePlunderRate = config('game_balance.attack.plunder_percent');
       
       if (config('game_balance.vault_protection.enabled')) {
           return $this->calculateVaultProtectedPlunder($defender, $basePlunderRate);
       }
       
       // Fallback to old behavior
       return (int)($defender->credits * $basePlunderRate);
   }
   ```

2. **Add vault protection calculation**
   - See 04-PROTECTION-SYSTEMS.md for complete formula

**Estimated Work**: 2-3 hours

---

## Phase 3: Attack Service (Newbie Protection & Deployment Costs)

### File: `/app/Controllers/BattleController.php`

**Changes Required**:

1. **Check newbie protection before attack**
   ```php
   $protectionStatus = $this->checkNewbieProtection($defender, $attacker);
   if ($protectionStatus['blocked']) {
       return error($protectionStatus['reason']);
   }
   ```

2. **Charge deployment costs**
   ```php
   $deploymentCost = $this->battleService->calculateDeploymentCost($attacker, 'attack');
   if ($attacker->credits < $deploymentCost) {
       return error("Insufficient credits for deployment");
   }
   $attacker->credits -= $deploymentCost;
   ```

3. **Apply partial protection multiplier**
   ```php
   if ($protectionStatus['partial']) {
       $plunder *= config('game_balance.newbie_protection.plunder_multiplier_partial');
   }
   ```

**Estimated Work**: 3-4 hours

---

## Phase 4: Spy Service (Deployment Costs)

### File: `/app/Controllers/SpyController.php` & `/app/Models/Services/SpyService.php`

**Changes Required**:

1. **Charge spy deployment costs**
   - Similar to attack deployment (Phase 3)
   - Use `cost_per_1k_spy_power` config

2. **Check newbie protection for spy missions**
   - Same logic as attacks

**Estimated Work**: 2 hours

---

## Phase 5: Transfer/Donation Services (Anti-Laundering)

### Files: `/app/Controllers/BankController.php`, `/app/Controllers/AllianceFundingController.php`

**Changes Required**:

1. **Block outbound transfers for protected accounts**
   ```php
   if ($this->isNewbieProtected($sender)) {
       return error("Cannot send credits while protected (anti-laundering)");
   }
   ```

2. **Cap alliance donations for protected accounts**
   ```php
   if ($this->isNewbieProtected($donor)) {
       $cap = config('game_balance.anti_laundering.max_alliance_donation_per_day_protected');
       // Check daily total, enforce cap
   }
   ```

**Estimated Work**: 2 hours

---

## Phase 6: Activity Tracking (Weekly Bonus System)

### File: `/app/Models/Services/ActivityService.php` (new file)

**Changes Required**:

1. **Create activity tracking table**
   ```sql
   CREATE TABLE activity_log (
       id BIGINT PRIMARY KEY AUTO_INCREMENT,
       user_id BIGINT NOT NULL,
       action_type VARCHAR(50),
       points_earned INT,
       target_user_id BIGINT NULL,
       week_number INT,
       created_at TIMESTAMP
   );
   ```

2. **Track actions and award points**
   ```php
   public function recordAction(User $user, string $action, ?User $target = null)
   {
       // Check weekly cap
       // Check anti-bot rules (unique targets)
       // Award points
   }
   ```

3. **Calculate weekly bonus**
   ```php
   public function calculateActivityBonus(User $user): float
   {
       $points = $this->getWeeklyPoints($user);
       $capped = min($points, config('game_balance.activity_bonus.weekly_points_cap'));
       return $capped * config('game_balance.activity_bonus.income_bonus_per_point');
   }
   ```

**Estimated Work**: 4-5 hours

---

## Phase 7: Multiplier Clamping (Budget Enforcement)

### File: `/app/Models/Services/TurnProcessorService.php` & `/app/Models/Services/BattleService.php`

**Changes Required**:

1. **Implement multiplier calculation with budgets**
   - See 07-MULTIPLIER-CAPS.md for complete formula

2. **Clamp income multipliers**
   ```php
   $structureBonus = min($rawStructureBonus, 0.50);
   $allianceBonus = min($rawAllianceBonus, 0.30);
   // ... etc
   $total = min($structureBonus + $allianceBonus + ..., 1.00);
   ```

3. **Clamp combat multipliers**
   - Similar logic, different caps (combat_power.total_max = 1.20)

**Estimated Work**: 3-4 hours

---

## Phase 8: Alliance Treasury Interest Removal

### File: `/app/Models/Services/AllianceTreasuryService.php`

**Changes Required**:

1. **Skip interest calculation**
   ```php
   // OLD (remove)
   $interest = $treasury->balance * config('game_balance.alliance_treasury.bank_interest_rate');
   $treasury->balance += $interest;
   
   // NEW (skip)
   // No interest calculation
   ```

**Estimated Work**: 30 minutes

---

## Phase 9: Database Migrations

**New Columns Required**:

```sql
ALTER TABLE users ADD COLUMN readiness DECIMAL(5,2) DEFAULT 1.00;
ALTER TABLE users ADD COLUMN last_combat_at TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN activity_points_this_week INT DEFAULT 0;
ALTER TABLE users ADD COLUMN week_number INT DEFAULT 0;
ALTER TABLE users ADD COLUMN total_attacks_sent INT DEFAULT 0;
```

**Estimated Work**: 1 hour

---

## Testing Checklist

- [ ] Unit test: `calculateAccountingFirmBonus()`
- [ ] Unit test: `calculateVaultProtectedPlunder()`
- [ ] Unit test: `calculateMaintenance()`
- [ ] Unit test: `calculateDeploymentCost()`
- [ ] Unit test: `checkNewbieProtection()`
- [ ] Unit test: `calculateActivityBonus()`
- [ ] Unit test: `calculateIncomeMultipliers()` with budgets
- [ ] Integration test: Full turn processing with new systems
- [ ] Integration test: Attack with vault protection + newbie protection
- [ ] Integration test: Weekly activity reset
- [ ] Simulation test: 90-day archetype comparison

---

## Total Estimated Work: 24-30 hours

### Breakdown
- Turn processor: 4-6 hours
- Vault protection: 2-3 hours
- Attack service: 3-4 hours
- Spy service: 2 hours
- Anti-laundering: 2 hours
- Activity tracking: 4-5 hours
- Multiplier clamping: 3-4 hours
- Alliance treasury: 0.5 hours
- Database migrations: 1 hour
- Testing: 3-5 hours

---

Next: [10-MIGRATION-STRATEGY.md](10-MIGRATION-STRATEGY.md)
