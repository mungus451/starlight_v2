<?php

namespace Tests\Unit;

use Mockery;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use PDO;
use PDOStatement;
use App\Core\Config;

/**
 * Base TestCase for Unit Tests
 * 
 * Provides helper methods for mocking common dependencies:
 * - PDO database connections
 * - Config objects
 * - Repositories
 * - Services
 */
abstract class TestCase extends PHPUnitTestCase
{
    /**
     * Clean up Mockery expectations after each test
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create a mock PDO instance
     * 
     * @return PDO|Mockery\MockInterface
     */
    protected function createMockPDO(): PDO
    {
        return Mockery::mock(PDO::class);
    }

    /**
     * Create a mock PDOStatement
     * 
     * @return PDOStatement|Mockery\MockInterface
     */
    protected function createMockStatement(): PDOStatement
    {
        return Mockery::mock(PDOStatement::class);
    }

    /**
     * Create a mock Config instance with optional preset values
     * 
     * Example:
     * $config = $this->createMockConfig([
     *     'game_balance.training.soldiers' => ['credits' => 15000, 'citizens' => 1],
     *     'game_balance.attack.energy_cost' => 25
     * ]);
     * 
     * @param array $values Key-value pairs for config values
     * @return Config|Mockery\MockInterface
     */
    protected function createMockConfig(array $values = []): Config
    {
        $mock = Mockery::mock(Config::class);
        
        $mock->shouldReceive('get')->with('game_balance.attack.power_per_soldier', 1)->andReturn(1)->byDefault();
        $mock->shouldReceive('get')->with('game_balance.attack.power_per_guard', 1)->andReturn(1)->byDefault();
        $mock->shouldReceive('get')->with('game_balance.spy.base_power_per_spy', 1)->andReturn(1)->byDefault();
        $mock->shouldReceive('get')->with('game_balance.spy.base_power_per_sentry', 1)->andReturn(1)->byDefault();

        foreach ($values as $key => $value) {
            $mock->shouldReceive('get')
                ->with($key)
                ->andReturn($value);
        }

        return $mock;
    }

    /**
     * Create a partial mock of a class using Mockery
     * Useful for mocking only specific methods while keeping others real
     * 
     * @param string $className Fully qualified class name
     * @param array $constructorArgs Arguments to pass to constructor
     * @return Mockery\MockInterface
     */
    protected function createMockeryPartialMock(string $className, array $constructorArgs = []): Mockery\MockInterface
    {
        return Mockery::mock($className, $constructorArgs)->makePartial();
    }

    /**
     * Create a spy - a mock that allows method calls and records them
     * 
     * @param string $className Fully qualified class name
     * @return Mockery\MockInterface
     */
    protected function createSpy(string $className): Mockery\MockInterface
    {
        return Mockery::spy($className);
    }

    /**
     * Assert that a ServiceResponse is successful
     * 
     * @param \App\Core\ServiceResponse $response
     * @param string $message Optional custom assertion message
     */
    protected function assertServiceSuccess($response, string $message = ''): void
    {
        $this->assertTrue(
            $response->isSuccess(),
            $message ?: "Expected ServiceResponse to be successful, but it failed with: {$response->message}"
        );
    }

    /**
     * Assert that a ServiceResponse is a failure
     * 
     * @param \App\Core\ServiceResponse $response
     * @param string $message Optional custom assertion message
     */
    protected function assertServiceFailure($response, string $message = ''): void
    {
        $this->assertFalse(
            $response->isSuccess(),
            $message ?: "Expected ServiceResponse to fail, but it succeeded"
        );
    }

    /**
     * Assert that a ServiceResponse contains specific data
     * 
     * @param \App\Core\ServiceResponse $response
     * @param string $key Data key to check
     * @param mixed $expectedValue Expected value
     */
    protected function assertServiceData($response, string $key, $expectedValue): void
    {
        $this->assertArrayHasKey($key, $response->data, "ServiceResponse data missing key: {$key}");
        $this->assertEquals($expectedValue, $response->data[$key], "ServiceResponse data[{$key}] does not match expected value");
    }

    /**
     * Assert that a ServiceResponse message contains a substring
     * 
     * @param \App\Core\ServiceResponse $response
     * @param string $needle Substring to search for
     */
    protected function assertServiceMessageContains($response, string $needle): void
    {
        $this->assertStringContainsString(
            $needle,
            $response->message,
            "ServiceResponse message does not contain expected text: {$needle}"
        );
    }
}
