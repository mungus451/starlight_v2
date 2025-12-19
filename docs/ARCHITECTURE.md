# StarlightDominion V2 Architecture Guide

## Overview

StarlightDominion V2 is built on a strict **Model-View-Controller-Service (MVC-S)** architecture pattern. The core principle is **Separation of Concerns**: business logic is never mixed with presentation or database queries.

## Project Structure

```
starlight_v2/
├── app/
│   ├── Controllers/    # C: Handles HTTP I/O
│   ├── Core/           # Framework utilities (Database, Session, Config, CSRF)
│   ├── Middleware/     # Request guards (Authentication, Rate Limiting)
│   ├── Events/         # Event definitions
│   ├── Listeners/      # Event handlers
│   ├── Presenters/     # View formatting logic
│   └── Models/
│       ├── Entities/       # D: Dumb Data Objects (DTOs)
│       ├── Repositories/   # R: Data Access Layer (RAW SQL)
│       └── Services/       # S: Business Logic Layer (The "M" orchestrator)
├── config/             # Game balance and environment variables
├── cron/               # Game loop scripts (turn processing, NPCs)
├── database/           # Phinx migrations and seeds
├── migrations/         # One-time data migration scripts
├── public/             # Web entry point (index.php only)
├── tests/              # Validation and test scripts
└── views/              # V: HTML templates
```

## Application Flow: From Request to Response

A typical feature follows this sequence:

| Layer | File/Component | Responsibility |
|-------|----------------|----------------|
| **Router** | `public/index.php` | Maps URI to Controller::method. Enforces AuthMiddleware. |
| **Middleware** | `App\Middleware\*` | Validates authentication, rate limits, permissions. |
| **Controller** | `App\Controllers\*` | 1. Receives Request<br>2. Calls Service<br>3. Renders View<br><br>**Never contains business logic or SQL**. |
| **Service** | `App\Models\Services\*` | 1. Orchestrates Logic<br>2. Validates input<br>3. Begins transaction<br>4. Orchestrates multiple Repository calls<br>5. Applies game balance rules (from `config/`) |
| **Repository** | `App\Models\Repositories\*` | 1. Executes SQL<br>2. Contains raw parameterized queries<br>3. Fetches data and returns Entities<br><br>**Never contains game logic or knows about HTTP/sessions**. |
| **Entity** | `App\Models\Entities\*` | Readonly data containers representing database rows. |
| **Database** | `App\Core\Database.php` | Provides a single, transactional PDO connection instance. |
| **View** | `views/*/` | Presents data. Consumes data passed by Controller and renders HTML (including CSRF tokens from BaseController). |

## Detailed Layer Responsibilities

### 1. Controllers (`app/Controllers/`)

Controllers handle **HTTP concerns only**:

```php
class TrainingController extends BaseController
{
    public function __construct(
        private TrainingService $trainingService
    ) {
        parent::__construct();
    }

    public function train(): void
    {
        // 1. Validate CSRF
        $this->csrfService->validateToken($_POST['csrf_token'] ?? '');
        
        // 2. Get user input
        $unitType = $_POST['unit_type'] ?? '';
        $quantity = (int)($_POST['quantity'] ?? 0);
        
        // 3. Call service (business logic)
        $result = $this->trainingService->trainUnits(
            $this->session->get('user_id'),
            $unitType,
            $quantity
        );
        
        // 4. Set flash message
        $this->session->setFlash('success', $result['message']);
        
        // 5. Redirect
        $this->redirect('/training');
    }
}
```

**Rules:**
- No SQL queries
- No business logic calculations
- No direct database access
- Only orchestrates calls to Services
- Handles redirects and flash messages

### 2. Services (`app/Models/Services/`)

Services contain **ALL business logic**:

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
        // Validate input
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be positive');
        }

        // Load game balance
        $cost = Config::get("game_balance.training.{$unitType}.cost");
        $totalCost = $cost * $quantity;

        // Start transaction
        $this->db->beginTransaction();
        try {
            // Load current state
            $user = $this->userRepository->findById($userId);
            $resources = $this->resourceRepository->findByUserId($userId);

            // Validate business rules
            if ($resources->money < $totalCost) {
                throw new InsufficientFundsException();
            }

            // Execute changes
            $this->resourceRepository->deductMoney($userId, $totalCost);
            $this->userRepository->incrementUnit($userId, $unitType, $quantity);

            $this->db->commit();

            return [
                'success' => true,
                'message' => "Trained {$quantity} {$unitType}(s)"
            ];
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
```

**Rules:**
- All game calculations and business rules
- Orchestrates multiple repository calls
- Always wraps multi-table operations in transactions
- Validates business rules (not just input validation)
- Returns Entity objects or arrays
- Never knows about HTTP (no $_POST, no redirects)

### 3. Repositories (`app/Models/Repositories/`)

Repositories handle **database queries only**:

```php
class UserRepository
{
    public function __construct(private PDO $db) {}

    public function findById(int $id): ?User
    {
        $stmt = $this->db->prepare('
            SELECT * FROM users WHERE id = :id
        ');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? User::fromArray($row) : null;
    }

    public function incrementUnit(int $userId, string $unitType, int $amount): void
    {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET {$unitType} = {$unitType} + :amount
            WHERE id = :user_id
        ");
        $stmt->execute([
            'amount' => $amount,
            'user_id' => $userId
        ]);
    }

    public function create(string $email, string $username, string $passwordHash): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO users (email, username, password, created_at)
            VALUES (:email, :username, :password, NOW())
        ');
        $stmt->execute([
            'email' => $email,
            'username' => $username,
            'password' => $passwordHash
        ]);
        
        return (int)$this->db->lastInsertId();
    }
}
```

**Rules:**
- Only SQL queries (SELECT, INSERT, UPDATE, DELETE)
- Returns Entity objects or arrays of Entities
- Never contains business logic
- Never validates business rules (only data integrity)
- Method naming: `findById()`, `findByEmail()`, `create()`, `update()`, `delete()`

### 4. Entities (`app/Models/Entities/`)

Entities are **readonly data containers**:

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

**Rules:**
- All properties are `readonly`
- No business logic methods
- Only data transformation methods (`fromArray()`, `toArray()`)
- Represents database rows or complex data structures

### 5. Views (`views/`)

Views are **"dumb" templates**:

```php
<!-- views/training/index.php -->
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<h1>Training Center</h1>

<div class="resources">
    <p>Money: $<?= number_format($money) ?></p>
</div>

<form method="POST" action="/training/train">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    
    <label>
        Unit Type:
        <select name="unit_type">
            <option value="soldiers">Soldiers</option>
            <option value="guards">Guards</option>
            <option value="spies">Spies</option>
        </select>
    </label>
    
    <label>
        Quantity:
        <input type="number" name="quantity" min="1" required>
    </label>
    
    <button type="submit">Train Units</button>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
```

**Rules:**
- Only presentation logic (formatting, loops, conditionals)
- No business logic calculations
- No direct database queries
- Variables passed from Controller
- CSRF tokens automatically available from `BaseController`

## Configuration Management

All game mechanics live in `/config/` files:

### `config/game_balance.php`
```php
return [
    'training' => [
        'soldiers' => [
            'cost' => 100,
            'attack_power' => 5,
            'defense_power' => 3,
        ],
        'guards' => [
            'cost' => 150,
            'attack_power' => 2,
            'defense_power' => 8,
        ],
        // ...
    ],
    'structures' => [
        'barracks' => [
            'base_cost' => 1000,
            'cost_multiplier' => 1.15,
            'income_bonus' => 0.05,
        ],
        // ...
    ],
];
```

### `config/armory_items.php`
Weapon and armor stats, costs, unlock requirements.

### `config/bank.php`
Interest rates, transfer fees, deposit limits.

## Dependency Injection

The application uses **PHP-DI** for dependency injection:

```php
// config/dependencies.php
use function DI\autowire;
use function DI\get;

return [
    PDO::class => function () {
        return Database::getInstance()->getConnection();
    },
    
    // Services auto-wire their dependencies
    TrainingService::class => autowire(),
    AttackService::class => autowire(),
    
    // Controllers auto-wire their services
    TrainingController::class => autowire(),
];
```

Controllers and Services declare dependencies in constructors, and PHP-DI automatically resolves them.

## Transaction Management

All multi-table operations **must** use transactions:

```php
$this->db->beginTransaction();
try {
    // Multiple repository calls
    $this->resourceRepository->deductMoney($userId, $cost);
    $this->userRepository->incrementUnit($userId, 'soldiers', $quantity);
    $this->statsRepository->recordTraining($userId, $quantity);
    
    $this->db->commit();
} catch (Throwable $e) {
    $this->db->rollBack();
    throw $e;
}
```

This ensures data consistency - either all changes succeed or none do.

## Error Handling

- **Validation Errors**: Throw custom exceptions (`InsufficientFundsException`, `InvalidInputException`)
- **Controllers**: Catch exceptions, set flash messages, redirect
- **Development**: Set `APP_ENV=development` in `.env` for full error display
- **Production**: Errors logged to `/logs/error.log`

## Security Patterns

### CSRF Protection
```php
// In Controller
$this->csrfService->validateToken($_POST['csrf_token'] ?? '');

// In View
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
```

### SQL Injection Prevention
```php
// Always use prepared statements
$stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id');
$stmt->execute(['id' => $userId]);
```

### Authentication
```php
// Middleware protects routes
$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/training', [TrainingController::class, 'index']);
    // AuthMiddleware applied in index.php
});
```

## Testing

- **MVC Compliance**: `tests/verify_mvc_compliance.php`
- **DI Resolution**: `tests/verify_di_resolution.php`
- **Session Decoupling**: `tests/VerifySessionDecoupling.php`
- **Alliance Bonuses**: `tests/AllianceStructureBonusTest.php`

## Further Reading

- [CONTRIBUTING.md](../CONTRIBUTING.md) - Development workflow and adding new features
- [README.md](../README.md) - Installation and setup
- [DOCKER.md](DOCKER.md) - Docker deployment guide
