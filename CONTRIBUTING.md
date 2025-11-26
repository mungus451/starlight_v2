# Contributing to StarlightDominion V2

Thank you for your interest in contributing to StarlightDominion V2! This guide will help you understand our development workflow and architectural patterns.

## Table of Contents

- [Development Setup](#development-setup)
- [Architecture Overview](#architecture-overview)
- [Adding a New Feature](#adding-a-new-feature)
- [Code Standards](#code-standards)
- [Testing](#testing)
- [Git Workflow](#git-workflow)

## Development Setup

### Prerequisites

- PHP 8.4+
- MariaDB or MySQL
- Composer
- Docker (recommended)

### Quick Start

```bash
# Clone the repository
git clone https://github.com/mungus451/starlight_v2.git
cd starlight_v2

# Using Docker (Recommended)
cp .env.example .env
docker-compose up -d
docker exec starlight_app composer install
docker exec starlight_app composer phinx migrate

# Manual Setup
composer install
cp .env.example .env
# Configure your .env database credentials
php vendor/bin/phinx migrate
php -S localhost:8000 -t public
```

### Development Environment

- **Main Branch**: `master` (production-ready code)
- **Feature Branches**: `feature/your-feature-name`
- **Bug Fixes**: `fix/issue-description`

## Architecture Overview

StarlightDominion V2 follows a strict **MVC-S (Model-View-Controller-Service)** pattern. Please read [ARCHITECTURE.md](ARCHITECTURE.md) for detailed architecture documentation.

### Key Principles

1. **Separation of Concerns**: Business logic stays in Services, queries stay in Repositories
2. **Dependency Injection**: Use constructor injection for all dependencies
3. **Transaction Safety**: Wrap multi-table operations in database transactions
4. **Immutable Entities**: All Entity properties are `readonly`
5. **No Magic**: Explicit dependencies, no service locators

## Adding a New Feature

When implementing a new "vertical slice" feature (e.g., "Item Repair System"), follow this explicit structure:

### Step 1: Define the Data (Entity)

Create `app/Models/Entities/ItemRepair.php` to represent the database structure:

```php
<?php

namespace App\Models\Entities;

readonly class ItemRepair
{
    public function __construct(
        public int $id,
        public int $userId,
        public int $itemId,
        public int $repairCost,
        public string $createdAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)$data['id'],
            userId: (int)$data['user_id'],
            itemId: (int)$data['item_id'],
            repairCost: (int)$data['repair_cost'],
            createdAt: $data['created_at'],
        );
    }
}
```

### Step 2: Define Database Access (Repository)

Create `app/Models/Repositories/ItemRepairRepository.php` for all SQL queries:

```php
<?php

namespace App\Models\Repositories;

use App\Models\Entities\ItemRepair;
use PDO;

class ItemRepairRepository
{
    public function __construct(private PDO $db) {}

    public function findById(int $id): ?ItemRepair
    {
        $stmt = $this->db->prepare('
            SELECT * FROM item_repairs WHERE id = :id
        ');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? ItemRepair::fromArray($row) : null;
    }

    public function findByUserId(int $userId): array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM item_repairs 
            WHERE user_id = :user_id
            ORDER BY created_at DESC
        ');
        $stmt->execute(['user_id' => $userId]);

        return array_map(
            fn($row) => ItemRepair::fromArray($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function create(int $userId, int $itemId, int $cost): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO item_repairs (user_id, item_id, repair_cost, created_at)
            VALUES (:user_id, :item_id, :cost, NOW())
        ');
        $stmt->execute([
            'user_id' => $userId,
            'item_id' => $itemId,
            'cost' => $cost,
        ]);

        return (int)$this->db->lastInsertId();
    }
}
```

### Step 3: Define Business Logic (Service)

Create `app/Models/Services/ItemRepairService.php` to handle all business rules:

```php
<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Models\Repositories\ItemRepairRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\ArmoryRepository;
use PDO;

class ItemRepairService
{
    public function __construct(
        private PDO $db,
        private ItemRepairRepository $itemRepairRepository,
        private ResourceRepository $resourceRepository,
        private ArmoryRepository $armoryRepository,
    ) {}

    public function repairItem(int $userId, int $itemId): array
    {
        // Load game balance config
        $baseRepairCost = Config::get('game_balance.repair.base_cost');

        $this->db->beginTransaction();
        try {
            // Fetch current state
            $item = $this->armoryRepository->findById($itemId);
            $resources = $this->resourceRepository->findByUserId($userId);

            // Validate ownership
            if ($item->userId !== $userId) {
                throw new \InvalidArgumentException('Item does not belong to user');
            }

            // Calculate repair cost based on item damage
            $repairCost = (int)($baseRepairCost * $item->damagePercent);

            // Validate resources
            if ($resources->money < $repairCost) {
                throw new \RuntimeException('Insufficient funds for repair');
            }

            // Execute changes
            $this->resourceRepository->deductMoney($userId, $repairCost);
            $this->armoryRepository->repairItem($itemId);
            $this->itemRepairRepository->create($userId, $itemId, $repairCost);

            $this->db->commit();

            return [
                'success' => true,
                'message' => "Item repaired for \${$repairCost}",
                'cost' => $repairCost,
            ];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getRepairHistory(int $userId): array
    {
        return $this->itemRepairRepository->findByUserId($userId);
    }
}
```

### Step 4: Define HTTP Interface (Controller)

Create `app/Controllers/ItemRepairController.php`:

```php
<?php

namespace App\Controllers;

use App\Models\Services\ItemRepairService;
use App\Models\Services\ArmoryService;

class ItemRepairController extends BaseController
{
    public function __construct(
        private ItemRepairService $itemRepairService,
        private ArmoryService $armoryService,
    ) {
        parent::__construct();
    }

    public function index(): void
    {
        $userId = $this->session->get('user_id');
        
        $data = [
            'items' => $this->armoryService->getUserItems($userId),
            'repairHistory' => $this->itemRepairService->getRepairHistory($userId),
            'csrf_token' => $this->csrfService->generateToken(),
        ];

        $this->render('item_repair/index', $data);
    }

    public function repair(): void
    {
        // Validate CSRF
        $this->csrfService->validateToken($_POST['csrf_token'] ?? '');

        $userId = $this->session->get('user_id');
        $itemId = (int)($_POST['item_id'] ?? 0);

        try {
            $result = $this->itemRepairService->repairItem($userId, $itemId);
            $this->session->setFlash('success', $result['message']);
        } catch (\RuntimeException $e) {
            $this->session->setFlash('error', $e->getMessage());
        }

        $this->redirect('/repair');
    }
}
```

### Step 5: Define the View

Create `views/item_repair/index.php`:

```php
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<h1>Item Repair Shop</h1>

<?php if ($flash = $this->session->getFlash('success')): ?>
    <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
<?php endif; ?>

<?php if ($flash = $this->session->getFlash('error')): ?>
    <div class="alert alert-error"><?= htmlspecialchars($flash) ?></div>
<?php endif; ?>

<h2>Your Items</h2>
<?php if (empty($items)): ?>
    <p>No items to repair.</p>
<?php else: ?>
    <?php foreach ($items as $item): ?>
        <div class="item-card">
            <h3><?= htmlspecialchars($item->name) ?></h3>
            <p>Condition: <?= 100 - $item->damagePercent ?>%</p>
            
            <?php if ($item->damagePercent > 0): ?>
                <form method="POST" action="/repair/repair">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="item_id" value="<?= $item->id ?>">
                    <button type="submit">Repair</button>
                </form>
            <?php else: ?>
                <p>Item is in perfect condition</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<h2>Repair History</h2>
<?php if (empty($repairHistory)): ?>
    <p>No repair history.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Item</th>
                <th>Cost</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($repairHistory as $repair): ?>
                <tr>
                    <td><?= htmlspecialchars($repair->createdAt) ?></td>
                    <td>Item #<?= $repair->itemId ?></td>
                    <td>$<?= number_format($repair->repairCost) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
```

### Step 6: Register the Routes

Update `public/index.php`:

```php
use App\Controllers\ItemRepairController;

$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
    // ... existing routes ...
    
    // Item Repair routes
    $r->addRoute('GET', '/repair', [ItemRepairController::class, 'index']);
    $r->addRoute('POST', '/repair/repair', [ItemRepairController::class, 'repair']);
});
```

### Step 7: Create Database Migration

Create a new Phinx migration:

```bash
composer phinx create CreateItemRepairsTable
```

Edit `database/migrations/YYYYMMDDHHMMSS_create_item_repairs_table.php`:

```php
<?php

use Phinx\Migration\AbstractMigration;

class CreateItemRepairsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('item_repairs');
        $table->addColumn('user_id', 'integer')
              ->addColumn('item_id', 'integer')
              ->addColumn('repair_cost', 'integer')
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
              ->addForeignKey('item_id', 'armory_items', 'id', ['delete' => 'CASCADE'])
              ->create();
    }
}
```

Run the migration:

```bash
php vendor/bin/phinx migrate
```

### Step 8: Add Game Balance Configuration

Update `config/game_balance.php`:

```php
return [
    // ... existing config ...
    
    'repair' => [
        'base_cost' => 50,
        'cost_multiplier' => 1.5,
    ],
];
```

## Code Standards

### PHP Standards

- **PHP Version**: 8.4+
- **PSR-12**: Follow PSR-12 coding style
- **Type Hints**: Always use strict types and return type declarations
- **Readonly**: Use `readonly` for Entity properties

### Naming Conventions

- **Classes**: PascalCase (`UserService`, `ItemRepairController`)
- **Methods**: camelCase (`findById`, `trainUnits`)
- **Variables**: camelCase (`$userId`, `$repairCost`)
- **Constants**: SCREAMING_SNAKE_CASE (`MAX_UNITS`)
- **Database Tables**: snake_case (`item_repairs`, `alliance_members`)

### Documentation

- Add PHPDoc blocks for public methods
- Explain complex business logic with inline comments
- Update ARCHITECTURE.md if introducing new patterns

Example:

```php
/**
 * Repairs a damaged item for a user.
 *
 * @param int $userId The user performing the repair
 * @param int $itemId The item to repair
 * @return array Success status and message
 * @throws \RuntimeException If user has insufficient funds
 */
public function repairItem(int $userId, int $itemId): array
{
    // Implementation
}
```

## Testing

### Running Tests

```bash
# Verify MVC compliance
php tests/verify_mvc_compliance.php

# Verify dependency injection
php tests/verify_di_resolution.php

# Verify session decoupling
php tests/VerifySessionDecoupling.php

# Run alliance structure tests
php tests/AllianceStructureBonusTest.php
```

### Manual Testing

1. Start the development server: `php -S localhost:8000 -t public`
2. Test your feature thoroughly in the browser
3. Check error logs: `tail -f logs/error.log`
4. Test edge cases (insufficient funds, invalid input, etc.)

### Test Checklist

- [ ] Feature works as expected
- [ ] CSRF protection implemented
- [ ] Input validation present
- [ ] Database transactions used correctly
- [ ] Flash messages display properly
- [ ] Error handling works
- [ ] No SQL injection vulnerabilities
- [ ] Follows MVC-S pattern
- [ ] Code passes compliance tests

## Git Workflow

### Branch Naming

- Features: `feature/item-repair-system`
- Bug Fixes: `fix/alliance-tax-calculation`
- Refactoring: `refactor/user-repository`
- Documentation: `docs/architecture-guide`

### Commit Messages

Follow the [Conventional Commits](https://www.conventionalcommits.org/) specification:

```
feat: Add item repair system
fix: Correct alliance tax calculation
docs: Update architecture guide
refactor: Extract power calculation to service
test: Add alliance bonus tests
chore: Update dependencies
```

### Pull Request Process

1. **Create Feature Branch**
   ```bash
   git checkout -b feature/item-repair-system
   ```

2. **Make Changes**
   - Follow the architecture patterns
   - Write clear, focused commits
   - Test thoroughly

3. **Push and Create PR**
   ```bash
   git push origin feature/item-repair-system
   ```
   - Open a pull request against `master`
   - Describe what the PR does
   - Reference any related issues

4. **Code Review**
   - Address reviewer feedback
   - Ensure tests pass
   - Keep commits clean

5. **Merge**
   - Squash small commits if needed
   - Merge when approved

## Common Patterns

### Loading Game Balance Config

```php
use App\Core\Config;

$cost = Config::get('game_balance.training.soldiers.cost');
$multiplier = Config::get('game_balance.structures.barracks.cost_multiplier');
```

### Transaction Pattern

```php
$this->db->beginTransaction();
try {
    // Multiple repository calls
    $this->db->commit();
} catch (\Throwable $e) {
    $this->db->rollBack();
    throw $e;
}
```

### Flash Messages

```php
// Set flash
$this->session->setFlash('success', 'Operation completed!');
$this->session->setFlash('error', 'Something went wrong!');

// Display in view
<?php if ($flash = $this->session->getFlash('success')): ?>
    <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
<?php endif; ?>
```

### CSRF Protection

```php
// Generate token (in Controller)
$data['csrf_token'] = $this->csrfService->generateToken();

// Validate token (in POST handler)
$this->csrfService->validateToken($_POST['csrf_token'] ?? '');

// Include in form
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
```

## Questions or Issues?

- Check [ARCHITECTURE.md](ARCHITECTURE.md) for architectural details
- Review existing code for examples
- Open an issue for questions or suggestions
- Join our development discussions

Thank you for contributing to StarlightDominion V2!
