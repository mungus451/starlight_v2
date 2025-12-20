# Bank System

The bank allows players to store credits securely with interest accumulation and charge-based deposit limits.

## Configuration

Bank settings are defined in `config/bank.php`:

```php
return [
    'interest_rate' => 0.0005,  // 0.05% per turn
    'deposit_max_charges' => 3,
    'deposit_charge_regen_hours' => 4,
    'deposit_percent_limit' => 0.8,  // Can deposit max 80% of credits
    'transfer_fee_percent' => 0.01,  // 1% fee on transfers
];
```

## Deposit System

### Charge Regeneration

Deposits use a charge system to prevent rapid-fire banking:

```php
// From BankService.php
public function getBankData(int $userId): array {
    $stats = $this->statsRepo->findByUserId($userId);
    $bankConfig = $this->config->get('bank');
    
    $currentCharges = $stats->deposit_charges;
    $maxCharges = $bankConfig['deposit_max_charges'];

    if ($currentCharges < $maxCharges && $stats->last_deposit_at !== null) {
        $lastDepositTime = new DateTime($stats->last_deposit_at);
        $now = new DateTime();
        
        $diffSeconds = $now->getTimestamp() - $lastDepositTime->getTimestamp();
        $hoursPassed = $diffSeconds / 3600;
        $regenHours = $bankConfig['deposit_charge_regen_hours'];
        
        $chargesToRegen = (int)floor($hoursPassed / $regenHours);
        // ... regenerate charges
    }
}
```

### Deposit Flow

1. **Validate charge availability**
2. **Check amount limit** (max 80% of on-hand credits)
3. **Execute transaction**:
   - Deduct from `credits`
   - Add to `banked_credits`
   - Consume one deposit charge

## Withdraw System

Withdrawals are unlimited and instantaneous.

## Transfer System

Players can transfer credits with a 1% fee:

- **Validation**: Recipient must exist
- **Fee calculation**: `$fee = floor($amount * 0.01)`
- **Transaction**: Atomic transfer with fee deduction

## Interest Accumulation

Interest is processed by `cron/process_turn.php` every 5 minutes:

```php
$interest = floor($user['banked_credits'] * $bankConfig['interest_rate']);
```

## UI Components

The bank view (`views/bank/show.php`) includes:
- Real-time charge countdown timer
- Max deposit button (calculates 80% limit)
- Formatted number inputs
- CSRF-protected forms
