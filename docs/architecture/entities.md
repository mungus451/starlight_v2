# Entities

Readonly data containers that represent database rows.

```php
readonly class User
{
    public function __construct(
        public int $id,
        public string $email,
        public string $username,
        public int $money,
        public int $soldiers,
        public int $guards,
        public int $workers,
        public int $spies,
        public ?int $allianceId,
        public string $createdAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)$data['id'],
            email: $data['email'],
            username: $data['username'],
            money: (int)$data['money'],
            soldiers: (int)$data['soldiers'],
            guards: (int)$data['guards'],
            workers: (int)$data['workers'],
            spies: (int)$data['spies'],
            allianceId: $data['alliance_id'] ? (int)$data['alliance_id'] : null,
            createdAt: $data['created_at'],
        );
    }
}
```

Rules:
- All properties are `readonly`
- No business logic
- Provide simple transforms such as `fromArray()`
