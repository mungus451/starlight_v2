# Unit Testing Guide

## Overview

This project uses **PHPUnit 10.5** and **Mockery** for unit testing. The test suite focuses on testing Service layer business logic with mocked dependencies, ensuring fast, isolated tests without database interaction.

## Running Tests

### Run All Unit Tests
```bash
./vendor/bin/phpunit
```

### Run Specific Test Suite
```bash
./vendor/bin/phpunit --testsuite Unit
```

### Run Specific Test File
```bash
./vendor/bin/phpunit tests/Unit/Services/TrainingServiceTest.php
```

### Run Specific Test Method
```bash
./vendor/bin/phpunit --filter testTrainUnitsSuccessfullyTrainsSoldiers
```

### Run with Coverage (requires Xdebug or PCOV)
```bash
./vendor/bin/phpunit --coverage-html coverage
```

## Test Structure

```
tests/
├── bootstrap.php                          # PHPUnit bootstrap - loads autoloader and .env
├── Unit/
│   ├── TestCase.php                      # Base test case with helper methods
│   └── Services/
│       └── TrainingServiceTest.php       # Example Service test
├── verify_refactor.php                   # Legacy integration test (kept for reference)
└── VerifySessionDecoupling.php           # Legacy architectural audit (kept for reference)
```

## Writing Unit Tests

### Test Class Template

```php
<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\YourService;
use App\Core\Config;
use Mockery;

class YourServiceTest extends TestCase
{
    private YourService $service;
    private Config|Mockery\MockInterface $mockConfig;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockConfig = Mockery::mock(Config::class);
        $this->service = new YourService($this->mockConfig);
    }

    public function testSomething(): void
    {
        // Arrange
        $this->mockConfig
            ->shouldReceive('get')
            ->once()
            ->with('some.config.key')
            ->andReturn(['value' => 123]);

        // Act
        $result = $this->service->doSomething();

        // Assert
        $this->assertTrue($result);
    }
}
```

## Mocking Patterns

### 1. Mock Config with Preset Values

```php
// Using helper method from TestCase
$config = $this->createMockConfig([
    'game_balance.training.soldiers' => ['credits' => 15000, 'citizens' => 1],
    'game_balance.attack.energy_cost' => 25
]);

// Manual Mockery
$mockConfig = Mockery::mock(Config::class);
$mockConfig->shouldReceive('get')
    ->with('game_balance.training.soldiers')
    ->andReturn(['credits' => 15000, 'citizens' => 1]);
```

### 2. Mock Repositories

```php
$mockResourceRepo = Mockery::mock(ResourceRepository::class);
$mockResourceRepo
    ->shouldReceive('findByUserId')
    ->once()
    ->with(1)
    ->andReturn($mockResource);

$mockResourceRepo
    ->shouldReceive('updateTrainedUnits')
    ->once()
    ->with(1, 50000, 10, 15, 5, 2, 1, 1)
    ->andReturn(true);
```

### 3. Mock PDO (for Repository tests)

```php
$mockPdo = $this->createMockPDO();
$mockStmt = $this->createMockStatement();

$mockPdo->shouldReceive('prepare')
    ->once()
    ->with('SELECT * FROM users WHERE id = ?')
    ->andReturn($mockStmt);

$mockStmt->shouldReceive('execute')
    ->once()
    ->with([1])
    ->andReturn(true);

$mockStmt->shouldReceive('fetch')
    ->once()
    ->andReturn(['id' => 1, 'email' => 'test@example.com']);
```

### 4. Create Entity Objects for Testing

```php
use App\Models\Entities\UserResource;

$mockResource = new UserResource(
    user_id: 1,
    credits: 100000,
    banked_credits: 0,
    gemstones: 0,
    naquadah_crystals: 0.0,
    untrained_citizens: 50,
    workers: 10,
    soldiers: 5,
    guards: 3,
    spies: 2,
    sentries: 1
);
```

## ServiceResponse Assertions

The `TestCase` base class provides custom assertions for `ServiceResponse` objects:

```php
// Assert success
$this->assertServiceSuccess($response);
$this->assertServiceSuccess($response, 'Custom failure message');

// Assert failure
$this->assertServiceFailure($response);

// Assert specific data values
$this->assertServiceData($response, 'units_trained', 5);

// Assert message contains text
$this->assertServiceMessageContains($response, 'Training complete');
```

## Test Organization Best Practices

### 1. Arrange-Act-Assert Pattern
Always structure tests with clear sections:

```php
public function testExample(): void
{
    // Arrange - Set up mocks and data
    $userId = 1;
    $this->mockConfig->shouldReceive('get')->andReturn(['value' => 100]);
    
    // Act - Execute the method under test
    $result = $this->service->doSomething($userId);
    
    // Assert - Verify the outcome
    $this->assertServiceSuccess($result);
}
```

### 2. Test Naming Convention
- Use descriptive test names: `testMethodName_Scenario_ExpectedBehavior`
- Examples:
  - `testTrainUnits_WithInsufficientCredits_ReturnsError`
  - `testTrainUnits_WithValidData_ReturnsSuccess`
  - `testGetTrainingData_ReturnsResourcesAndCosts`

### 3. One Assertion Focus Per Test
Each test should verify one specific behavior. Split complex scenarios into multiple tests.

### 4. Test Edge Cases
- Zero/negative values
- Null returns from dependencies
- Boundary conditions
- Invalid input types

## Common Testing Patterns

### Testing Validation Logic

```php
public function testRejectsNegativeAmount(): void
{
    $response = $this->service->trainUnits(1, 'soldiers', -5);
    
    $this->assertServiceFailure($response);
    $this->assertServiceMessageContains($response, 'must be a positive');
}
```

### Testing Cost Calculations

```php
public function testCalculatesCorrectCosts(): void
{
    $unitCost = ['credits' => 15000, 'citizens' => 1];
    $amount = 5;
    
    // Mock config and resources
    // ...
    
    // Verify repository called with correct calculated values
    $this->mockResourceRepo
        ->shouldReceive('updateTrainedUnits')
        ->once()
        ->with(
            1,                           // userId
            25000,                       // credits (100k - 75k)
            15,                          // citizens (20 - 5)
            Mockery::any(),              // use any() for unchanged values
            8,                           // soldiers (3 + 5)
            Mockery::any(),
            Mockery::any(),
            Mockery::any()
        )
        ->andReturn(true);
}
```

### Testing Database Failure Handling

```php
public function testHandlesDatabaseFailure(): void
{
    // Mock successful validation
    // ...
    
    // Mock repository to return false (failure)
    $this->mockResourceRepo
        ->shouldReceive('updateTrainedUnits')
        ->andReturn(false);
    
    $response = $this->service->trainUnits(1, 'soldiers', 5);
    
    $this->assertServiceFailure($response);
    $this->assertServiceMessageContains($response, 'database error');
}
```

## Mockery Expectations

### Call Counts
```php
->once()              // Called exactly once
->twice()             // Called exactly twice
->times(3)            // Called exactly 3 times
->atLeast()->times(1) // Called at least once
->never()             // Never called
```

### Argument Matching
```php
->with(1, 'test')           // Exact arguments
->with(Mockery::any())      // Any argument
->with(Mockery::type('int')) // Type checking
->withArgs(fn($arg) => $arg > 0) // Custom validation
```

### Return Values
```php
->andReturn(true)                    // Return specific value
->andReturn(true, false)             // Return different values on successive calls
->andReturnNull()                    // Return null
->andThrow(new Exception('Error'))   // Throw exception
```

## Configuration

### PHPUnit Configuration (`phpunit.xml`)
```xml
<phpunit bootstrap="tests/bootstrap.php" colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_DATABASE" value="starlightDB_test"/>
    </php>
</phpunit>
```

### Environment Configuration
Create `.env.testing` for test-specific configuration:
```env
APP_ENV=testing
DB_HOST=localhost
DB_DATABASE=starlightDB_test
DB_USERNAME=root
DB_PASSWORD=
```

## What to Test

### High Priority (Service Layer)
✅ **DO Test:**
- Business logic validation
- Cost calculations
- Resource checks
- Conditional branching
- ServiceResponse returns
- Error handling

❌ **DON'T Test:**
- Database queries (those are Repository tests)
- HTTP request/response handling (those are Controller tests)
- Framework internals
- Third-party library behavior

### Medium Priority (Repository Layer)
- CRUD operations (with test database or in-memory SQLite)
- Entity hydration
- Query correctness

### Low Priority (Entity Layer)
- Constructor validation
- Immutability enforcement

## Debugging Tests

### Run with Verbose Output
```bash
./vendor/bin/phpunit --verbose
```

### Run with Debug Output
```bash
./vendor/bin/phpunit --debug
```

### Stop on First Failure
```bash
./vendor/bin/phpunit --stop-on-failure
```

### Inspect Mockery Expectations
If a test fails with "Mockery expectation not met", add to test:
```php
// At end of test
Mockery::close(); // Already in TestCase::tearDown()
```

## Example Test: TrainingServiceTest

See `tests/Unit/Services/TrainingServiceTest.php` for a complete example demonstrating:
- Mocking Config and Repository dependencies
- Testing validation rules
- Testing success and failure paths
- Testing cost calculations
- Testing database error handling
- Using custom ServiceResponse assertions

## Next Steps

1. **Expand Service Tests**: Add tests for other Services like `BankService`, `AttackService`, `AllianceService`
2. **Add Repository Tests**: Create integration tests for Repositories (requires test database setup)
3. **Test Coverage**: Aim for >80% coverage on Service layer
4. **Continuous Integration**: Add PHPUnit to CI/CD pipeline

## Resources

- [PHPUnit Documentation](https://docs.phpunit.de/en/10.5/)
- [Mockery Documentation](http://docs.mockery.io/)
- [PHP-DI Documentation](https://php-di.org/)

## Troubleshooting

### Issue: "Class not found" errors
**Solution:** Run `composer dump-autoload`

### Issue: Mockery expectations not validated
**Solution:** Ensure `TestCase::tearDown()` calls `Mockery::close()`

### Issue: Tests pass but shouldn't
**Solution:** Verify mock expectations include `->once()` or specific call counts

### Issue: "PDO not mocked" errors
**Solution:** Inject mocked PDO into constructor, don't use `Database::getInstance()` in tests
