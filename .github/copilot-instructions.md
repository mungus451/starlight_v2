# StarlightDominion V2 - AI Agent Instructions

## Project Overview
StarlightDominion V2 is a browser-based space strategy MMO built with strict MVC architecture using PHP 8.3, MariaDB, and minimal dependencies (nikic/fast-route, vlucas/phpdotenv). The game features turn-based mechanics, alliances, combat, espionage, and resource management.

## Architecture Principles

### Core MVC Pattern
- **Models**: Split into Entities (data objects), Repositories (SQL queries), and Services (business logic)
- **Views**: "Dumb" PHP templates in `/views/` with shared layout in `/views/layouts/main.php`
- **Controllers**: Extend `BaseController`, handle HTTP requests, delegate to Services
- **Front Controller**: All requests route through `/public/index.php` using FastRoute

### Key Files to Understand
- `/public/index.php` - Entry point, routing configuration, error handling
- `/app/Controllers/BaseController.php` - Controller foundation with CSRF and session handling
- `/app/Core/Database.php` - Singleton PDO instance
- `/config/game_balance.php` - All game mechanics constants (costs, multipliers, formulas)

## Development Patterns

### Service Layer Architecture
Services in `/app/Models/Services/` contain ALL business logic:
```php
// Services inject repositories and handle transactions
public function __construct(
    PDO $db,
    UserRepository $userRepository,
    ResourceRepository $resourceRepository
) {
    $this->db = $db;
    $this->userRepository = $userRepository;
    $this->resourceRepository = $resourceRepository;
}

// Always wrap multi-table operations in transactions
$this->db->beginTransaction();
try {
    // Multiple repository calls
    $this->db->commit();
} catch (Throwable $e) {
    $this->db->rollback();
    throw $e;
}
```

### Repository Pattern
Repositories in `/app/Models/Repositories/` handle ONLY database queries:
- Methods follow naming: `findById()`, `findByEmail()`, `create()`, `update()`, `delete()`
- Return Entity objects or arrays of Entity objects
- Never contain business logic

### Entity Objects
Entities in `/app/Models/Entities/` are readonly data containers:
```php
class User {
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        // ... all properties readonly
    ) {}
}
```

### Controller Responsibilities
Controllers handle HTTP concerns only:
- Validate CSRF tokens using `$this->csrfService->validateToken()`
- Call appropriate Service methods
- Set flash messages via `$this->session->setFlash()`
- Redirect or render views

### Game Balance Configuration
All game mechanics live in `/config/` files:
- `/config/game_balance.php` - Unit costs, structure multipliers, combat formulas
- `/config/armory_items.php` - Equipment stats and costs
- `/config/bank.php` - Interest rates, transfer limits

## Development Commands

### Local Development
```bash
# Start development server (from project root)
php -S localhost:8000 -t public

# Run manual turn processing (game economy)
php cron/process_turn.php

# Install dependencies
composer install
```

### Database Setup
- Schema: `database.sql` documents V1→V2 migration (incremental ALTER statements)
- The live database has the complete V2 schema with denormalized `users` table
- Migrations: Run from project root: `php migrations/filename.php`
- Credentials: Configure via `.env` file (copy from `.env.example`)

## Common Patterns

### Adding New Game Features
1. Define balance constants in `/config/game_balance.php`
2. Create/update Entities for data structure
3. Add Repository methods for data access
4. Implement business logic in Services
5. Add Controller actions and routes in `/public/index.php`
6. Create view templates in appropriate `/views/` subdirectory

### CSRF Protection
All forms require CSRF tokens. BaseController auto-generates `$csrf_token` for views:
```php
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
```

### Authentication Flow
- AuthMiddleware protects routes
- Session data stored via Session class
- User authentication handled by AuthService
- Current user available in controllers via session

### Game Turn Processing
The cron system (`/cron/process_turn.php`) runs every 5 minutes:
- Processes income for all users
- Updates bank interest
- Handles time-based game mechanics
- Must be CLI-only for security

## File Organization
- `/app/Controllers/` - HTTP request handlers
- `/app/Models/Services/` - Business logic layer
- `/app/Models/Repositories/` - Database access layer  
- `/app/Models/Entities/` - Data objects
- `/app/Core/` - Framework utilities (Database, Session, CSRF)
- `/config/` - Game balance and settings
- `/views/` - Templates organized by feature
- `/public/` - Web-accessible files (index.php only)
- `/migrations/` - One-time database update scripts

## Security Notes
- Only `/public/` directory is web-accessible
- All forms use CSRF protection
- Sensitive operations require password confirmation
- Database uses prepared statements throughout
- Session handling via custom Session class

## Testing/Debugging
- Set `APP_ENV=development` in `.env` for error display
- Logs written to `/logs/` directory
- Use `var_dump()` and `error_log()` for debugging
- Database operations are transaction-wrapped for rollback safety