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
<<<<<<< HEAD:ARCHITECTURE.md
├── database/           # Phinx migrations and seeds        
├── public/             # Web entry point (index.php only)
=======
├── database/           # Phinx migrations and seeds
├── migrations/         # One-time data migration scripts
├── public/             # Front controller (index.php) plus static assets and diagnostic info.php
>>>>>>> 62b96af (docs: update documentation):docs/architecture/index.md
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

    ## Next Steps

    - [Project Structure](project-structure.md)
    - [Request Lifecycle](request-lifecycle.md)
    - [Controllers](controllers.md)
    - [Services](services.md)
    - [Repositories](repositories.md)
    - [Entities](entities.md)
    - [Views](views.md)
    - [Configuration & DI](config-di.md)
    - [Transactions & Errors](transactions.md)
    - [Security Patterns](security.md)
    - [Testing & Compliance](testing.md)
    - [CONTRIBUTING.md](../CONTRIBUTING.md)
    - [Getting Started](../getting-started/index.md)
    - [DOCKER.md](../DOCKER.md)


