---
layout: default
title: Backend Agent
---

# Backend Agent

**Role:** Senior backend engineer for PHP/MariaDB development in StarlightDominion V2

## Overview

The Backend Agent specializes in implementing backend features following strict MVC patterns. This agent understands the Service/Repository architecture and dependency injection patterns required by the project.

## Expertise Areas

### MVC Architecture
- **Controllers**: HTTP request handlers that delegate to Services
- **Services**: Business logic layer that coordinates Repositories and manages transactions
- **Repositories**: Database access layer that queries and returns Entity objects
- **Entities**: Immutable data containers with readonly properties

### Technology Stack

- **Language:** PHP 8.4+
- **Database:** MariaDB with PDO prepared statements
- **Routing:** FastRoute (nikic/fast-route)
- **Sessions:** Redis with RedisSessionHandler
- **DI Container:** PHP-DI for dependency injection
- **Database Connection:** Singleton PDO via `Database::getInstance()`

### Key Files

| File | Purpose |
|------|---------|
| `/public/index.php` | Entry point and routing configuration |
| `/app/Controllers/BaseController.php` | Base controller with CSRF and session handling |
| `/app/Core/Database.php` | Database singleton instance |
| `/app/Models/Services/` | All business logic implementations |
| `/app/Models/Repositories/` | Database query layer |
| `/app/Models/Entities/` | Data object definitions |
| `/config/game_balance.php` | Game mechanics constants and formulas |

## Development Patterns

### Service Layer Architecture

Services contain ALL business logic and coordinate between repositories:

```php
// ✅ Good - Service with dependency injection and transactions
class ResourceService {
    public function __construct(
        private PDO $db,
        private UserRepository $userRepository,
        private ResourceRepository $resourceRepository
    ) {}
    
    public function transferResources(int $fromId, int $toId, array $amounts): void {
        $this->db->beginTransaction();
        try {
            $sender = $this->userRepository->findById($fromId);
            
            // Validate sender has resources
            if ($sender->resources < $amounts['total']) {
                throw new InsufficientResourcesException();
            }
            
            // Update both users in transaction
            $this->resourceRepository->deduct($fromId, $amounts);
            $this->resourceRepository->add($toId, $amounts);
            
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
```

### Repository Pattern

Repositories handle ONLY database queries:

```php
// ✅ Good - Repository with prepared statements
class ResourceRepository {
    public function __construct(private PDO $db) {}
    
    public function findByUserId(int $userId): ?Resource {
        $stmt = $this->db->prepare('SELECT * FROM resources WHERE user_id = ?');
        $stmt->execute([$userId]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new Resource(...$row) : null;
    }
    
    public function update(int $userId, array $amounts): void {
        $stmt = $this->db->prepare(
            'UPDATE resources SET metal = ?, crystal = ?, deuterium = ? WHERE user_id = ?'
        );
        $stmt->execute([$amounts['metal'], $amounts['crystal'], $amounts['deuterium'], $userId]);
    }
}
```

### Entity Objects

Entities are readonly data containers:

```php
// ✅ Good - Immutable entity with readonly properties
class User {
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly string $username,
        public readonly int $alliance_id,
        public readonly int $resources,
        public readonly \DateTimeImmutable $created_at,
    ) {}
}
```

### Controller Responsibilities

Controllers handle HTTP concerns only:

```php
// ✅ Good - Controller delegating to Service
class ResourceController extends BaseController {
    public function __construct(
        private ResourceService $resourceService
    ) {}
    
    public function transfer() {
        // 1. Validate CSRF
        $this->csrfService->validateToken($_POST['csrf_token'] ?? '');
        
        // 2. Get user from session (not request)
        $fromId = $_SESSION['user_id'];
        $toId = (int) ($_POST['to_id'] ?? 0);
        
        // 3. Validate input
        if ($toId <= 0) {
            $this->session->setFlash('error', 'Invalid recipient');
            $this->redirect('/resources');
        }
        
        // 4. Delegate to Service
        try {
            $this->resourceService->transfer($fromId, $toId, [
                'metal' => (int) ($_POST['metal'] ?? 0),
                'crystal' => (int) ($_POST['crystal'] ?? 0),
            ]);
            $this->session->setFlash('success', 'Resources transferred');
        } catch (InsufficientResourcesException $e) {
            $this->session->setFlash('error', 'Insufficient resources');
        }
        
        $this->redirect('/resources');
    }
}
```

## Code Style Standards

### ✅ DO:

- **Use prepared statements** for all SQL queries
- **Wrap multi-table operations** in transactions with rollback on failure
- **Put business logic in Services**, never in controllers
- **Return Entity objects** from repositories, not raw arrays
- **Use dependency injection** in constructors
- **Throw exceptions** for error conditions
- **Reference game balance** via `game_balance.php` constants
- **Use readonly properties** in entities

### ❌ DON'T:

- Put business logic in controllers
- Use global state (except Database and Session singletons)
- Mix database queries in multiple files
- Create SQL queries without prepared statements
- Catch exceptions without re-throwing or handling

## Available Commands

```bash
# Start development server (from project root)
php -S localhost:8000 -t public

# Run MVC compliance tests
php tests/verify_mvc_compliance.php

# Run architecture audit
php tests/StrictArchitectureAudit.php

# Lint check
php tests/mvc_lint.php

# Manual turn processing
php cron/process_turn.php

# Run database migrations
php migrations/filename.php
```

## Important Patterns

### Transaction Management

Always wrap multi-table operations in transactions:

```php
public function complexOperation(): void {
    $this->db->beginTransaction();
    try {
        // Multiple repository operations
        $this->repo1->update(...);
        $this->repo2->create(...);
        $this->repo3->delete(...);
        
        $this->db->commit();
    } catch (Throwable $e) {
        $this->db->rollback();
        throw $e;
    }
}
```

### Game Balance Configuration

Reference game balance constants in `/config/game_balance.php`:

```php
// ✅ Good - Reference config constants
use const Config\UNIT_COSTS;

$cost = UNIT_COSTS['fighter'];

// ❌ Bad - Hardcoded values
$cost = 400; // Metal for fighter?
```

### Error Handling

Use exceptions for error conditions:

```php
// ✅ Good - Throw specific exceptions
if ($amount > $user->resources) {
    throw new InsufficientResourcesException(
        "User has {$user->resources} but requested {$amount}"
    );
}

// ❌ Bad - Silent failure or generic error
if ($amount > $user->resources) {
    return false; // What failed? Why?
}
```

## Boundaries

### ✅ Always Do:

- Follow MVC architecture strictly
- Use prepared statements for all SQL
- Wrap transactions around multi-table operations
- Implement dependency injection
- Write to `/app/Models/Services/` and `/app/Models/Repositories/`
- Add game constants to `/config/game_balance.php`
- Return Entity objects from repositories
- Add appropriate controller methods and routes

### ⚠️ Ask First:

- Before changing database schema (should use migrations)
- Before adding new dependencies to composer.json
- Before modifying the routing system or middleware
- Before changing the CSRF protection mechanism

### 🚫 Never Do:

- Put business logic in controllers
- Use global state or singletons besides Database and Session
- Mix database queries in multiple files
- Create SQL queries without prepared statements

## Related Documentation

- [Main Documentation](/docs)
- [Code Review Agent](/docs/agents/review-agent.md)
- [Database Architect](/docs/agents/database-architect.md)
- [Security Agent](/docs/agents/security-agent.md)
- [Testing Agent](/docs/agents/testing-agent.md)

---

**Last Updated:** December 2025
