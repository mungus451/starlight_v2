# Performance Guide

Optimization strategies across frontend, backend, and database layers in StarlightDominion V2.

## General Principles

1. **Measure First**: Profile before optimizing
2. **Optimize Hot Paths**: Focus on frequently-executed code
3. **Database Efficiency**: Minimize queries and use indexes
4. **Transaction Safety**: Balance atomicity with performance

## Database Optimization

### Use Indexes Strategically

```sql
-- Index foreign keys
CREATE INDEX idx_user_resources_user_id ON user_resources(user_id);

-- Index frequently queried columns
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_alliance_members_alliance_id ON alliance_members(alliance_id);
```

### Avoid N+1 Queries

```php
// ❌ Bad: N+1 query problem
foreach ($users as $user) {
    $resources = $resourceRepo->findByUserId($user->id);  // N queries
}

// ✅ Good: Single query with JOIN
$usersWithResources = $userRepo->findAllWithResources();  // 1 query
```

### Batch Operations

```php
// From BankService.php - Process interest for all users
public function processInterestForAll(): void {
    $stmt = $this->db->prepare("
        UPDATE user_resources 
        SET banked_credits = FLOOR(banked_credits * (1 + :rate))
        WHERE banked_credits > 0
    ");
    $stmt->execute(['rate' => $this->config->get('bank.interest_rate')]);
}
```

### Query Optimization

```php
// ❌ Bad: SELECT *
$stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");

// ✅ Good: Select only needed columns
$stmt = $this->db->prepare("
    SELECT id, empire_name, email, level 
    FROM users 
    WHERE id = :id
");
```

## Transaction Performance

### Minimize Transaction Scope

```php
// ❌ Bad: Long transaction with external calls
$this->db->beginTransaction();
$resources = $this->resourceRepo->findByUserId($userId);
sleep(5);  // Slow external API call
$this->resourceRepo->update($userId, $newResources);
$this->db->commit();

// ✅ Good: Short transaction, prepare data first
$resources = $this->resourceRepo->findByUserId($userId);
$externalData = $this->callExternalApi();  // Outside transaction

$this->db->beginTransaction();
$this->resourceRepo->update($userId, $newResources);
$this->db->commit();
```

### Bulk Operations in Transactions

```php
// From TrainingService.php
public function trainBulk(int $userId, array $orders): ServiceResponse {
    $this->db->beginTransaction();
    
    try {
        foreach ($orders as $unitType => $quantity) {
            $this->armyRepo->trainUnits($userId, $unitType, $quantity);
        }
        
        $this->db->commit();
        return ServiceResponse::success('Units trained!');
    } catch (\Throwable $e) {
        $this->db->rollback();
        throw $e;
    }
}
```

## Caching Strategies

### Configuration Caching

```php
// Load game balance once
private array $gameBalance;

public function __construct(Config $config) {
    $this->gameBalance = $config->get('game_balance');
}

public function calculateCost(string $structureKey, int $level): int {
    $config = $this->gameBalance['structures'][$structureKey];
    return (int)($config['base_cost'] * pow($config['multiplier'], $level - 1));
}
```

### Redis Session Storage

Session data is stored in Redis for fast access:

```php
// From RedisSessionHandler
public function read(string $id): string {
    return $this->redis->get("session:{$id}") ?: '';
}

public function write(string $id, string $data): bool {
    return $this->redis->setex("session:{$id}", $this->ttl, $data);
}
```

## Frontend Performance

### Minimize DOM Manipulation

```javascript
// ❌ Bad: Multiple DOM updates
for (let i = 0; i < items.length; i++) {
    document.getElementById('list').innerHTML += `<li>${items[i]}</li>`;
}

// ✅ Good: Single DOM update
const html = items.map(item => `<li>${item}</li>`).join('');
document.getElementById('list').innerHTML = html;
```

### Debounce User Input

```javascript
// From public/js/formatters.js
let debounceTimer;
input.addEventListener('input', (e) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        // Process input after 300ms of no typing
        formatNumber(e.target.value);
    }, 300);
});
```

### Lazy Loading

```php
<!-- Load non-critical CSS asynchronously -->
<link rel="preload" href="/css/styles.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="/css/styles.css"></noscript>
```

## Application Performance

### Dependency Injection Efficiency

```php
// ✅ Good: Singleton PDO instance
class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            self::$instance = new PDO(/* ... */);
        }
        return self::$instance;
    }
}
```

### Service Method Optimization

```php
// Avoid repeated repository calls
public function getAttackPageData(int $userId, int $page): array {
    // ❌ Bad: Multiple calls
    $user = $this->userRepo->findById($userId);
    $resources = $this->resourceRepo->findByUserId($userId);
    $army = $this->armyRepo->findByUserId($userId);
    
    // ✅ Better: Single join query in repository
    $data = $this->userRepo->findWithResourcesAndArmy($userId);
}
```

## Profiling Tools

### PHP Profiling

```bash
# Install Xdebug
sudo apt-get install php-xdebug

# Enable profiling in php.ini
xdebug.mode=profile
xdebug.output_dir=/tmp/xdebug

# Analyze with webgrind or kcachegrind
```

### Database Query Analysis

```php
// Log slow queries
if ($executionTime > 0.5) {
    error_log("Slow query ({$executionTime}s): {$sql}");
}
```

### MySQL Profiling

```sql
-- Enable profiling
SET profiling = 1;

-- Run query
SELECT * FROM users WHERE empire_name LIKE '%test%';

-- View profile
SHOW PROFILES;
SHOW PROFILE FOR QUERY 1;
```

## Benchmarking

### Load Testing with Apache Bench

```bash
# Test 1000 requests with 10 concurrent
ab -n 1000 -c 10 http://localhost:8000/dashboard

# Results show:
# - Requests per second
# - Time per request
# - Transfer rate
```

### PHP Benchmarking

```php
$start = microtime(true);

// Code to benchmark
for ($i = 0; $i < 1000; $i++) {
    $this->calculatePower($userId);
}

$duration = microtime(true) - $start;
echo "Execution time: {$duration}s\n";
```

## Optimization Checklist

### Database
- [ ] Indexes on foreign keys and frequently queried columns
- [ ] No SELECT * queries in production code
- [ ] Prepared statements for all queries
- [ ] Batch operations where possible
- [ ] Connection pooling configured

### Backend
- [ ] Transactions scoped appropriately
- [ ] Config loaded once and cached
- [ ] Singleton pattern for PDO
- [ ] Service methods avoid redundant repository calls
- [ ] Error handling doesn't leak stack traces

### Frontend
- [ ] Minimize DOM manipulations
- [ ] Debounce user input handlers
- [ ] Lazy load non-critical resources
- [ ] Compress and minify CSS/JS
- [ ] Use browser caching headers

## Performance Monitoring

### Application Logs

```php
// Log execution time for critical operations
$start = microtime(true);
$response = $this->attackService->executeAttack($attackerId, $defenderId);
$duration = microtime(true) - $start;

error_log("Attack execution time: {$duration}s");
```

### Turn Processing Performance

```bash
# Monitor cron job execution
time php cron/process_turn.php

# Output:
# real    0m2.341s
# user    0m1.234s
# sys     0m0.123s
```

## Related Topics

- [Architecture Guide](../architecture/index.md)
- [Security Guide](security.md)
