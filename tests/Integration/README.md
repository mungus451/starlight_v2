# Integration Tests

## Overview

Integration tests verify complete workflows with real database interactions. Unlike unit tests which mock dependencies, integration tests use the actual database to ensure all components work together correctly.

## Requirements

### Database Access
Integration tests require a running MySQL/MariaDB database with:
- Accessible host (update `DB_HOST` in `.env`)
- Valid credentials (`DB_USERNAME`, `DB_PASSWORD`)
- Database schema up to date
- Test database (recommended: separate from production)

### Configuration

For local testing, update `.env`:
```env
DB_HOST=localhost  # or 127.0.0.1 (not 'db' which is for Docker)
DB_DATABASE=starlightDB_test  # Separate test database recommended
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## Running Integration Tests

### Run Only Integration Tests
```bash
./vendor/bin/phpunit --testsuite Integration
```

### Run With Readable Output
```bash
./vendor/bin/phpunit --testsuite Integration --testdox
```

### Run Only Unit Tests (No Database Required)
```bash
./vendor/bin/phpunit --testsuite Unit
```

### Run All Tests
```bash
./vendor/bin/phpunit
```

## Test Isolation

Integration tests use **database transactions with automatic rollback** to ensure:
- ✅ Each test starts with a clean state
- ✅ Test data is automatically cleaned up
- ✅ Tests don't interfere with each other
- ✅ No manual cleanup required

### How It Works

```php
protected function setUp(): void
{
    // Start transaction before each test
    $this->db->beginTransaction();
    
    // Create test data
    $this->testUserId = $this->createTestUser();
}

protected function tearDown(): void
{
    // Rollback transaction after each test
    if ($this->db->inTransaction()) {
        $this->db->rollBack();
    }
}
```

This ensures all database changes made during the test are reverted, leaving no trace.

## Available Integration Tests

### TrainingIntegrationTest

Tests the complete unit training workflow with real database:

**Test Cases:**
1. ✅ `testTrainSoldiers_CompleteFlow_UpdatesDatabase` - Trains soldiers and verifies all database updates
2. ✅ `testTrainMultipleUnitTypes_InSequence_AllSucceed` - Trains workers, soldiers, and guards sequentially
3. ✅ `testTrainUnits_InsufficientCredits_FailsGracefully` - Verifies failure handling with insufficient resources
4. ✅ `testTrainUnits_DatabaseConstraint_RollsBackTransaction` - Tests transaction rollback behavior
5. ✅ `testGetTrainingData_ReturnsRealDatabaseData` - Verifies data retrieval from database

**What's Tested:**
- Complete service → repository → database flow
- Resource deduction (credits, citizens)
- Unit addition (soldiers, workers, guards)
- Multiple sequential operations
- Error handling with database constraints
- Transaction isolation

## Writing New Integration Tests

### Template

```php
<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Core\ContainerFactory;
use PDO;

class YourIntegrationTest extends TestCase
{
    private PDO $db;
    private YourService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        try {
            $container = ContainerFactory::createContainer();
            $this->db = $container->get(PDO::class);
            $this->service = $container->get(YourService::class);
            
            // Start transaction for isolation
            $this->db->beginTransaction();
            
            // Create test data
            $this->createTestData();
        } catch (\PDOException $e) {
            $this->markTestSkipped('Database not available');
        }
    }
    
    protected function tearDown(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        parent::tearDown();
    }
    
    public function testYourFeature(): void
    {
        // Arrange
        $initialState = $this->getStateFromDatabase();
        
        // Act
        $response = $this->service->doSomething();
        
        // Assert
        $this->assertTrue($response->isSuccess());
        $finalState = $this->getStateFromDatabase();
        $this->assertNotEquals($initialState, $finalState);
    }
}
```

## Best Practices

### 1. Use Transactions
Always wrap tests in transactions for automatic cleanup:
```php
$this->db->beginTransaction();  // setUp()
// ... test code ...
$this->db->rollBack();          // tearDown()
```

### 2. Test Complete Flows
Integration tests should verify entire workflows:
```php
// Good: Tests complete business flow
public function testCompleteUserRegistration(): void
{
    $userId = $this->authService->register($email, $password);
    $user = $this->userRepo->findById($userId);
    $resources = $this->resourceRepo->findByUserId($userId);
    $stats = $this->statsRepo->findByUserId($userId);
    
    $this->assertNotNull($user);
    $this->assertNotNull($resources);
    $this->assertNotNull($stats);
}

// Not ideal: Too granular for integration test
public function testUserRepositoryInsert(): void
{
    $userId = $this->userRepo->create($email, $name, $hash);
    $this->assertGreaterThan(0, $userId);
}
```

### 3. Verify Database State
Check actual database changes, not just return values:
```php
$response = $this->service->transferCredits($fromId, $toId, 1000);

// Verify both users' balances changed
$fromResources = $this->resourceRepo->findByUserId($fromId);
$toResources = $this->resourceRepo->findByUserId($toId);

$this->assertEquals($initialFrom - 1000, $fromResources->credits);
$this->assertEquals($initialTo + 1000, $toResources->credits);
```

### 4. Test Error Paths
Verify system behavior under failure conditions:
```php
public function testFailureHandling(): void
{
    // Force error condition
    $this->reduceCreditsToZero($userId);
    
    // Attempt operation
    $response = $this->service->purchaseItem($userId, $itemId);
    
    // Verify graceful failure
    $this->assertFalse($response->isSuccess());
    
    // Verify database unchanged
    $this->assertDatabaseUnchanged();
}
```

### 5. Use Descriptive Test Names
```php
// Good
public function testTrainSoldiers_WithSufficientResources_UpdatesAllTables(): void

// Not ideal
public function testTraining(): void
```

## Debugging Integration Tests

### View SQL Queries
Enable query logging in setUp():
```php
$this->db->setAttribute(PDO::ATTR_STATEMENT_CLASS, ['DebugPDOStatement']);
```

### Check Transaction State
```php
var_dump($this->db->inTransaction()); // Should be true during test
```

### Inspect Database State
```php
$stmt = $this->db->query("SELECT * FROM user_resources WHERE user_id = {$this->testUserId}");
var_dump($stmt->fetchAll());
```

### Test Without Rollback (Temporary)
```php
protected function tearDown(): void
{
    // Comment out rollback to inspect data
    // if ($this->db->inTransaction()) {
    //     $this->db->rollBack();
    // }
    parent::tearDown();
}
```

**Warning:** Remember to re-enable rollback after debugging!

## CI/CD Integration

### GitHub Actions Example
```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: starlightDB_test
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      
      - name: Install dependencies
        run: composer install
      
      - name: Run unit tests
        run: ./vendor/bin/phpunit --testsuite Unit
      
      - name: Run integration tests
        run: ./vendor/bin/phpunit --testsuite Integration
        env:
          DB_HOST: 127.0.0.1
          DB_DATABASE: starlightDB_test
          DB_USERNAME: root
          DB_PASSWORD: root
```

## Performance Considerations

Integration tests are slower than unit tests because they:
- Connect to real database
- Execute actual SQL queries
- Create/rollback transactions

**Optimization Tips:**
1. Run unit tests first (fast feedback)
2. Run integration tests before merging
3. Keep integration test count reasonable
4. Use test database on fast disk (SSD)
5. Consider parallel test execution for large suites

## Troubleshooting

### Database Connection Failed
```
Database Connection Error: SQLSTATE[HY000] [2002]
```
**Solution:** Check DB_HOST in .env (use `localhost` not `db`)

### Tests Don't Roll Back
```
Test data persists after test run
```
**Solution:** Ensure tearDown() calls `$this->db->rollBack()`

### Foreign Key Constraint Errors
```
SQLSTATE[23000]: Integrity constraint violation
```
**Solution:** Create test data in correct order (user → resources → stats)

### Tests Pass Individually, Fail Together
```
Tests interfere with each other
```
**Solution:** Verify each test properly rolls back transactions

---

**Status**: ✅ Integration test infrastructure ready  
**Framework**: PHPUnit 10.5 with database transactions  
**Isolation**: Automatic rollback after each test  
**Requirements**: MySQL/MariaDB accessible via .env configuration
