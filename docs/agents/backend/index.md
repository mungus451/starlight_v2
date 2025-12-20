---
layout: default
title: Backend Agent
---

# Backend Agent

**Role:** Senior backend engineer for PHP/MariaDB development in StarlightDominion V2

## Overview

The Backend Agent specializes in implementing backend features following strict MVC patterns. This agent understands the Service/Repository architecture and dependency injection patterns required by the project.

## Expertise Areas

- **MVC Architecture:** Controllers, Services, Repositories, Entities
- **PHP 8.4+** with strict typing and modern syntax
- **MariaDB** with PDO prepared statements
- **Service/Repository pattern** for clean separation of concerns
- **Dependency injection** via constructors
- **Game mechanics** implementation using config constants

## Key Files

| File | Purpose |
|------|---------|
| `/public/index.php` | Entry point and routing |
| `/app/Controllers/BaseController.php` | Base controller with CSRF handling |
| `/app/Models/Services/` | Business logic layer |
| `/app/Models/Repositories/` | Database query layer |
| `/app/Models/Entities/` | Data objects (readonly) |
| `/config/game_balance.php` | Game mechanics constants |

## Essential Pattern: Service with Transactions

```php
class ResourceService {
    public function __construct(
        private PDO $db,
        private ResourceRepository $resourceRepository
    ) {}
    
    public function transfer(int $fromId, int $toId, array $amounts): void {
        $this->db->beginTransaction();
        try {
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

## Essential Pattern: Repository with Prepared Statements

```php
class ResourceRepository {
    public function __construct(private PDO $db) {}
    
    public function findByUserId(int $userId): ?Resource {
        $stmt = $this->db->prepare('SELECT * FROM resources WHERE user_id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new Resource(...$row) : null;
    }
}
```

## Boundaries

### ✅ Always Do
- Use prepared statements for all SQL
- Wrap multi-table operations in transactions
- Put business logic in Services, not Controllers
- Return Entity objects from repositories
- Use dependency injection

### 🚫 Never Do
- Put business logic in controllers
- Create SQL without prepared statements
- Mix database queries across files

## Commands

```bash
# Start development server
php -S localhost:8000 -t public

# Run architecture audit
php tests/StrictArchitectureAudit.php

# Run migrations
php migrations/filename.php
```

---

**Last Updated:** December 2025
