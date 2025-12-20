# Transactions & Errors

Use transactions to ensure multi-table changes either fully apply or fully roll back.

```php
$this->db->beginTransaction();
try {
    $this->resourceRepository->deductMoney($userId, $cost);
    $this->userRepository->incrementUnit($userId, 'soldiers', $quantity);
    $this->statsRepository->recordTraining($userId, $quantity);
    $this->db->commit();
} catch (Throwable $e) {
    $this->db->rollBack();
    throw $e;
}
```

Guidelines:
- Throw domain-specific exceptions for validation failures
- Controllers catch, set flash messages, and redirect
- Development: enable verbose errors via `APP_ENV=development`
- Production: write to `logs/`
