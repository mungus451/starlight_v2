# Services

Contain all business logic. Load game balance config, validate rules, orchestrate repositories, and use transactions.

```php
class TrainingService
{
    public function __construct(
        private PDO $db,
        private UserRepository $userRepository,
        private ResourceRepository $resourceRepository
    ) {}

    public function trainUnits(int $userId, string $unitType, int $quantity): array
    {
        if ($quantity <= 0) throw new InvalidArgumentException('Quantity must be positive');
        $cost = Config::get("game_balance.training.{$unitType}.cost");
        $totalCost = $cost * $quantity;

        $this->db->beginTransaction();
        try {
            $resources = $this->resourceRepository->findByUserId($userId);
            if ($resources->money < $totalCost) throw new InsufficientFundsException();

            $this->resourceRepository->deductMoney($userId, $totalCost);
            $this->userRepository->incrementUnit($userId, $unitType, $quantity);
            $this->db->commit();
            return ['success' => true, 'message' => "Trained {$quantity} {$unitType}(s)"];
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
```

Rules:
- Business rules live here
- Orchestrate multiple repository calls
- Always wrap multi-table operations in transactions
- Never touch HTTP globals or perform redirects
