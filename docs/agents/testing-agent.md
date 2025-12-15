---
layout: default
title: Testing Agent
---

# Testing Agent

**Role:** Quality assurance specialist focused on testing and test coverage for StarlightDominion V2

## Overview

The Testing Agent specializes in comprehensive testing, test coverage, and preventing bugs. This agent focuses on reliability and preventing regressions through systematic testing strategies.

## Expertise Areas

### Testing Disciplines
- Unit testing for individual functions
- Integration testing for system interactions
- Game mechanics validation through simulation
- Architecture compliance testing
- Performance and edge case testing
- Regression prevention

### Technology Stack

- **Test Framework:** PHPUnit
- **Language:** PHP 8.4+
- **Custom Test Runners:** `/tests/` directory
- **Test Types:** Unit, Integration, Simulation, Architecture

### Test Files

| File | Purpose |
|------|---------|
| `BattleSimulationTest.php` | Battle mechanics validation |
| `GameLoopSimulationTest.php` | Turn processing and economy |
| `AllianceStructureBonusTest.php` | Alliance building bonuses |
| `verify_mvc_compliance.php` | Architecture validation |
| `StrictArchitectureAudit.php` | MVC pattern enforcement |
| `mvc_lint.php` | Code structure lint |
| `verify_di_resolution.php` | Dependency injection testing |
| `verify_refactor.php` | Refactoring validation |
| `VerifySessionDecoupling.php` | Session independence checks |

## Testing Standards

### Unit Test Structure

```php
// ✅ Good test - Comprehensive, isolated, clear assertions
class ResourceTransferTest extends TestCase {
    private ResourceService $service;
    private ResourceRepository $repository;
    private UserRepository $userRepository;
    private PDO $db;
    
    protected function setUp(): void {
        // Set up test database connection
        $this->db = $this->createTestDatabase();
        
        // Create repositories
        $this->repository = new ResourceRepository($this->db);
        $this->userRepository = new UserRepository($this->db);
        
        // Create service with mocked/real dependencies
        $this->service = new ResourceService(
            $this->db,
            $this->userRepository,
            $this->repository
        );
    }
    
    protected function tearDown(): void {
        // Clean up test database
        $this->dropTestDatabase();
    }
    
    public function testTransferResourcesSuccessfully(): void {
        // Arrange
        $sender = $this->userRepository->create(['name' => 'Sender', 'resources' => 1000]);
        $recipient = $this->userRepository->create(['name' => 'Recipient', 'resources' => 0]);
        
        // Act
        $this->service->transfer($sender->id, $recipient->id, 100);
        
        // Assert
        $updatedSender = $this->userRepository->findById($sender->id);
        $updatedRecipient = $this->userRepository->findById($recipient->id);
        
        $this->assertEquals(900, $updatedSender->resources);
        $this->assertEquals(100, $updatedRecipient->resources);
    }
    
    public function testTransferFailsWithInsufficientResources(): void {
        // Arrange
        $sender = $this->userRepository->create(['name' => 'Sender', 'resources' => 50]);
        $recipient = $this->userRepository->create(['name' => 'Recipient', 'resources' => 0]);
        
        // Act & Assert
        $this->expectException(InsufficientResourcesException::class);
        $this->service->transfer($sender->id, $recipient->id, 100);
        
        // Verify transaction rolled back
        $this->assertEquals(50, $this->userRepository->findById($sender->id)->resources);
        $this->assertEquals(0, $this->userRepository->findById($recipient->id)->resources);
    }
    
    public function testTransferToSelfIsNoop(): void {
        // Arrange
        $user = $this->userRepository->create(['name' => 'User', 'resources' => 1000]);
        
        // Act
        $this->service->transfer($user->id, $user->id, 100);
        
        // Assert (no change)
        $this->assertEquals(1000, $this->userRepository->findById($user->id)->resources);
    }
    
    public function testTransferValidatesAmounts(): void {
        // Arrange
        $sender = $this->userRepository->create(['resources' => 1000]);
        $recipient = $this->userRepository->create(['resources' => 0]);
        
        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->service->transfer($sender->id, $recipient->id, -100); // Negative
    }
}

// ❌ Poor test - Unclear, untestable, missing cases
class ResourceTest extends TestCase {
    public function testResource(): void {
        $resource = new Resource(100);
        $this->assertNotNull($resource);  // What are we testing?
    }
    
    public function testTransfer(): void {
        // No setup, unclear what's being tested
        $this->assertTrue(true);  // This test is meaningless
    }
}
```

### Arrange-Act-Assert Pattern

Every test should follow this structure:

```php
public function testSomeFeature(): void {
    // 1. ARRANGE - Set up test data and state
    $user = $this->createUser(['name' => 'Test', 'level' => 1]);
    $structure = $this->createStructure(['type' => 'factory', 'level' => 1]);
    
    // 2. ACT - Execute the code being tested
    $result = $this->service->buildStructure($user->id, $structure->id);
    
    // 3. ASSERT - Verify the results
    $this->assertInstanceOf(BuildingResult::class, $result);
    $this->assertEquals(10, $result->experience_gained);
    $this->assertTrue($result->success);
}
```

## Test Coverage Areas

### Service Layer Tests

Test all business logic:

```php
// Test successful operations
public function testCreateResourceSuccessfully(): void { ... }

// Test validation and constraints
public function testCreateResourceValidatesAmount(): void { ... }
public function testCreateResourceEnforcesMaximum(): void { ... }

// Test error conditions
public function testCreateResourceFailsWithInvalidUser(): void { ... }
public function testCreateResourceRollsBackOnDatabaseError(): void { ... }

// Test transaction safety
public function testCreateResourceTransactionRollsBack(): void { ... }

// Test edge cases
public function testCreateResourceWithZeroAmount(): void { ... }
public function testCreateResourceWithMaxValue(): void { ... }
```

### Repository Layer Tests

Test data access patterns:

```php
// Test CRUD operations
public function testFindByIdReturnsEntity(): void { ... }
public function testFindByIdReturnsNullWhenNotFound(): void { ... }
public function testCreateInsertEntity(): void { ... }
public function testUpdateModifiesEntity(): void { ... }
public function testDeleteRemovesEntity(): void { ... }

// Test queries
public function testFindByUserIdReturnsAllUserResources(): void { ... }
public function testFindActiveReturnsOnlyActiveRecords(): void { ... }

// Test entity integrity
public function testCreateReturnsEntityWithId(): void { ... }
public function testEntityIsImmutable(): void { ... }
```

### Game Mechanics Tests

Test complex mechanics:

```php
class BattleSimulationTest extends TestCase {
    public function testAttackerWinsWithSuperiorForce(): void {
        // Arrange
        $attacker = $this->createUnits(['fighter' => 100]);
        $defender = $this->createUnits(['fighter' => 50]);
        
        // Act
        $result = $this->battleService->resolveBattle($attacker, $defender);
        
        // Assert
        $this->assertTrue($result->attacker_won);
        $this->assertEquals(0, $result->defender_survivors);
    }
    
    public function testDefenderWinsWithSuperiorDefense(): void { ... }
    public function testBattleCalculatesCasualtiesCorrectly(): void { ... }
    public function testBattleLogsResults(): void { ... }
}
```

### Architecture Compliance Tests

Validate MVC patterns:

```bash
# Run architecture validation
php tests/verify_mvc_compliance.php
php tests/StrictArchitectureAudit.php
php tests/mvc_lint.php

# These checks verify:
# - Controllers only contain HTTP logic
# - Services contain business logic
# - Repositories handle only database queries
# - No circular dependencies
# - Proper dependency injection
```

## Edge Cases and Boundary Testing

Always test edge cases:

```php
class TransferTest extends TestCase {
    // Boundary tests
    public function testTransferMinimumAmount(): void { ... }
    public function testTransferMaximumAmount(): void { ... }
    public function testTransferWithExactBalance(): void { ... }
    
    // Null/empty tests
    public function testTransferWithNullRecipient(): void { ... }
    public function testTransferWithEmptyAmount(): void { ... }
    
    // Concurrent operation tests
    public function testTransfersConcurrentlyToSameUser(): void { ... }
    public function testTransfersFromSameUserConcurrently(): void { ... }
    
    // Data type tests
    public function testTransferWithFloatAmount(): void { ... }
    public function testTransferWithNegativeAmount(): void { ... }
    public function testTransferWithNonNumericAmount(): void { ... }
}
```

## Performance Testing

Test for performance regressions:

```php
public function testProductionCalculationPerformance(): void {
    // Arrange
    $users = $this->createUsers(1000);
    
    // Act & Assert
    $start = microtime(true);
    $this->economyService->processTurn();
    $duration = microtime(true) - $start;
    
    // Should complete in reasonable time
    $this->assertLessThan(5, $duration, 'Turn processing too slow');
}
```

## Test Data Fixtures

Create consistent test data:

```php
class TestFixtures {
    public static function createUser(array $overrides = []): User {
        return new User(
            id: 1,
            email: $overrides['email'] ?? 'test@example.com',
            username: $overrides['username'] ?? 'testuser',
            resources: $overrides['resources'] ?? 1000,
            level: $overrides['level'] ?? 1,
            alliance_id: $overrides['alliance_id'] ?? null,
        );
    }
    
    public static function createStructure(array $overrides = []): Structure {
        return new Structure(
            id: 1,
            user_id: $overrides['user_id'] ?? 1,
            type: $overrides['type'] ?? 'factory',
            level: $overrides['level'] ?? 1,
        );
    }
}

// Usage
$user = TestFixtures::createUser(['level' => 10]);
$structure = TestFixtures::createStructure(['user_id' => $user->id]);
```

## Regression Testing

Prevent re-introduction of fixed bugs:

```php
public function testBugFixedIssue123(): void {
    // This test captures a bug that was previously fixed
    // It prevents the bug from being reintroduced
    
    // Bug: Resources were negative after transfer
    $user = $this->createUser(['resources' => 100]);
    
    // Try to transfer more than available
    $this->expectException(InsufficientResourcesException::class);
    $this->resourceService->transfer($user->id, 2, 150);
}
```

## Continuous Integration

Tests should run automatically:

```bash
# Run all tests
php tests/verify_mvc_compliance.php
php tests/StrictArchitectureAudit.php
php tests/mvc_lint.php

# Run specific test class
php tests/BattleSimulationTest.php

# Run with coverage reporting
phpunit --coverage-html coverage/ tests/
```

## Test Organization

```
tests/
├── BattleSimulationTest.php      # Battle mechanics
├── GameLoopSimulationTest.php    # Economy and turns
├── AllianceStructureBonusTest.php # Alliance features
├── StrictArchitectureAudit.php   # MVC compliance
├── verify_mvc_compliance.php     # Architecture validation
├── mvc_lint.php                  # Code lint checks
├── verify_di_resolution.php      # DI container validation
├── verify_refactor.php           # Refactoring validation
├── VerifySessionDecoupling.php   # Session handling
└── README_TESTS.md               # Test documentation
```

## Test Checklist

When writing tests:

- [ ] Test has descriptive name describing what's being tested
- [ ] Test uses Arrange-Act-Assert pattern
- [ ] Test is isolated and doesn't depend on other tests
- [ ] Test cleans up after itself (tearDown)
- [ ] Test has both success and failure cases
- [ ] Test covers edge cases and boundaries
- [ ] Test verifies error conditions with exceptions
- [ ] Test uses meaningful assertions with messages
- [ ] Test doesn't test implementation details, only behavior
- [ ] Test is fast (< 1 second typically)

## Boundaries

### ✅ Always Do:

- Write tests for new features before implementing (TDD)
- Test both success and failure paths
- Test edge cases and boundaries
- Use clear, descriptive test names
- Follow Arrange-Act-Assert pattern
- Test business logic in Services
- Test data access in Repositories
- Verify transaction safety
- Run architecture compliance tests
- Document why non-obvious tests exist

### ⚠️ Ask First:

- Before removing test coverage
- Before skipping tests to make deadline
- Before testing implementation details
- Before disabling test failures

### 🚫 Never Do:

- Test implementation details instead of behavior
- Skip tests because they're hard to write
- Mix multiple concerns in one test
- Write tests without assertions
- Rely solely on manual testing
- Test the test framework instead of code

## Available Commands

```bash
# Run MVC compliance test
php tests/verify_mvc_compliance.php

# Run architecture audit
php tests/StrictArchitectureAudit.php

# Run lint check
php tests/mvc_lint.php

# Run battle simulation
php tests/BattleSimulationTest.php

# Run game loop simulation
php tests/GameLoopSimulationTest.php

# Run alliance structure bonus test
php tests/AllianceStructureBonusTest.php
```

## Related Documentation

- [Main Documentation](/docs)
- [Backend Agent](/docs/agents/backend-agent.md)
- [Security Agent](/docs/agents/security-agent.md)
- [Code Review Agent](/docs/agents/review-agent.md)

---

**Last Updated:** December 2025
