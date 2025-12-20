# Repositories

Data access layer. Execute parameterized SQL only and return Entities.

```php
class UserRepository
{
    public function __construct(private PDO $db) {}

    public function findById(int $id): ?User
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? User::fromArray($row) : null;
    }

    public function incrementUnit(int $userId, string $unitType, int $amount): void
    {
        $stmt = $this->db->prepare("UPDATE users SET {$unitType} = {$unitType} + :amount WHERE id = :user_id");
        $stmt->execute(['amount' => $amount, 'user_id' => $userId]);
    }
}
```

Rules:
- Only SQL (SELECT/INSERT/UPDATE/DELETE)
- Never contain business logic
- Use clear method names: `findById`, `findByEmail`, `create`, `update`, `delete`
