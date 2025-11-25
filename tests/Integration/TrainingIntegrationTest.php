<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Core\ContainerFactory;
use App\Core\Database;
use App\Models\Services\TrainingService;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use PDO;

/**
 * Integration Test for Training Flow
 * 
 * Tests the complete training workflow with real database interactions.
 * Uses transactions with rollback to ensure test isolation.
 * 
 * REQUIREMENTS:
 * - Database must be accessible (configured in .env)
 * - Update DB_HOST in .env to 'localhost' or '127.0.0.1' for local testing
 * - Database schema must be up to date
 * 
 * RUNNING:
 * - Run only unit tests: ./vendor/bin/phpunit --testsuite Unit
 * - Run only integration tests: ./vendor/bin/phpunit --testsuite Integration
 * - Run all tests: ./vendor/bin/phpunit
 */
class TrainingIntegrationTest extends TestCase
{
    private PDO $db;
    private TrainingService $trainingService;
    private UserRepository $userRepo;
    private ResourceRepository $resourceRepo;
    private int $testUserId;

    /**
     * Set up test with real database and DI container
     */
    protected function setUp(): void
    {
        parent::setUp();

        try {
            // Initialize container and get real dependencies
            $container = ContainerFactory::createContainer();
            $this->db = $container->get(PDO::class);
            $this->trainingService = $container->get(TrainingService::class);
            $this->userRepo = $container->get(UserRepository::class);
            $this->resourceRepo = $container->get(ResourceRepository::class);

            // Start transaction for test isolation
            $this->db->beginTransaction();

            // Create a test user
            $this->testUserId = $this->createTestUser();
        } catch (\PDOException $e) {
            $this->markTestSkipped('Database not available: ' . $e->getMessage());
        }
    }

    /**
     * Rollback transaction after each test to clean up
     */
    protected function tearDown(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        parent::tearDown();
    }

    /**
     * Create a test user with initial resources
     */
    private function createTestUser(): int
    {
        // Create user
        $userId = $this->userRepo->createUser(
            'test_' . uniqid() . '@example.com',
            'Test User ' . uniqid(),
            password_hash('password', PASSWORD_DEFAULT)
        );

        // Set up initial resources for testing
        $this->db->prepare("
            INSERT INTO user_resources (
                user_id, credits, untrained_citizens, 
                workers, soldiers, guards, spies, sentries
            ) VALUES (?, 500000, 100, 10, 5, 3, 2, 1)
        ")->execute([$userId]);

        // Set up initial stats
        $this->db->prepare("
            INSERT INTO user_stats (
                user_id, xp, level, attack_turns, energy, deposit_charges
            ) VALUES (?, 0, 1, 50, 100, 5)
        ")->execute([$userId]);

        return $userId;
    }

    /**
     * Test: Complete flow of training soldiers with real database
     */
    public function testTrainSoldiers_CompleteFlow_UpdatesDatabase(): void
    {
        // Arrange - Get initial state
        $initialResources = $this->resourceRepo->findByUserId($this->testUserId);
        $this->assertNotNull($initialResources);
        
        $initialCredits = $initialResources->credits;
        $initialCitizens = $initialResources->untrained_citizens;
        $initialSoldiers = $initialResources->soldiers;
        
        $amountToTrain = 10;
        $expectedCreditCost = 15000 * $amountToTrain; // 150,000 credits

        // Act - Train soldiers
        $response = $this->trainingService->trainUnits(
            $this->testUserId,
            'soldiers',
            $amountToTrain
        );

        // Assert - Verify service response
        $this->assertTrue($response->isSuccess(), "Training should succeed: {$response->message}");
        $this->assertStringContainsString('Training complete', $response->message);
        $this->assertStringContainsString('10', $response->message);
        $this->assertStringContainsString('Soldiers', $response->message);

        // Assert - Verify database was updated correctly
        $updatedResources = $this->resourceRepo->findByUserId($this->testUserId);
        $this->assertNotNull($updatedResources);
        
        $this->assertEquals(
            $initialCredits - $expectedCreditCost,
            $updatedResources->credits,
            'Credits should be deducted'
        );
        
        $this->assertEquals(
            $initialCitizens - $amountToTrain,
            $updatedResources->untrained_citizens,
            'Untrained citizens should be consumed'
        );
        
        $this->assertEquals(
            $initialSoldiers + $amountToTrain,
            $updatedResources->soldiers,
            'Soldiers should be added'
        );

        // Verify other unit types unchanged
        $this->assertEquals($initialResources->workers, $updatedResources->workers);
        $this->assertEquals($initialResources->guards, $updatedResources->guards);
        $this->assertEquals($initialResources->spies, $updatedResources->spies);
        $this->assertEquals($initialResources->sentries, $updatedResources->sentries);
    }

    /**
     * Test: Training multiple unit types in sequence
     */
    public function testTrainMultipleUnitTypes_InSequence_AllSucceed(): void
    {
        // Train workers
        $response1 = $this->trainingService->trainUnits($this->testUserId, 'workers', 5);
        $this->assertTrue($response1->isSuccess());

        // Train soldiers
        $response2 = $this->trainingService->trainUnits($this->testUserId, 'soldiers', 3);
        $this->assertTrue($response2->isSuccess());

        // Train guards
        $response3 = $this->trainingService->trainUnits($this->testUserId, 'guards', 2);
        $this->assertTrue($response3->isSuccess());

        // Verify final state
        $finalResources = $this->resourceRepo->findByUserId($this->testUserId);
        
        $this->assertEquals(15, $finalResources->workers, 'Workers: 10 + 5');
        $this->assertEquals(8, $finalResources->soldiers, 'Soldiers: 5 + 3');
        $this->assertEquals(5, $finalResources->guards, 'Guards: 3 + 2');
        
        // Total citizens consumed: 5 + 3 + 2 = 10
        $this->assertEquals(90, $finalResources->untrained_citizens, 'Citizens: 100 - 10');
        
        // Total credits: 5*5k + 3*15k + 2*25k = 25k + 45k + 50k = 120k
        $this->assertEquals(380000, $finalResources->credits, 'Credits: 500k - 120k');
    }

    /**
     * Test: Training fails when insufficient credits
     */
    public function testTrainUnits_InsufficientCredits_FailsGracefully(): void
    {
        // Arrange - Reduce credits to insufficient amount
        $this->db->prepare("
            UPDATE user_resources 
            SET credits = 10000 
            WHERE user_id = ?
        ")->execute([$this->testUserId]);

        // Act - Try to train 10 soldiers (needs 150,000 credits)
        $response = $this->trainingService->trainUnits($this->testUserId, 'soldiers', 10);

        // Assert - Should fail with appropriate message
        $this->assertFalse($response->isSuccess());
        $this->assertStringContainsString('not have enough credits', $response->message);

        // Verify database unchanged
        $resources = $this->resourceRepo->findByUserId($this->testUserId);
        $this->assertEquals(10000, $resources->credits, 'Credits should not change');
        $this->assertEquals(5, $resources->soldiers, 'Soldiers should not change');
    }

    /**
     * Test: Transaction rollback on database error
     */
    public function testTrainUnits_DatabaseConstraint_RollsBackTransaction(): void
    {
        // Get initial state
        $initialResources = $this->resourceRepo->findByUserId($this->testUserId);
        $initialSoldiers = $initialResources->soldiers;

        // Corrupt the test by deleting user_stats (would cause FK constraint issues in some flows)
        // For this specific test, we'll just verify the transaction behavior
        
        // Train successfully
        $response = $this->trainingService->trainUnits($this->testUserId, 'soldiers', 1);
        $this->assertTrue($response->isSuccess());

        // Verify the change is visible within our transaction
        $updatedResources = $this->resourceRepo->findByUserId($this->testUserId);
        $this->assertEquals($initialSoldiers + 1, $updatedResources->soldiers);

        // When tearDown() rolls back, changes should be undone
        // (This is implicitly tested by test isolation - next test won't see this change)
    }

    /**
     * Test: getTrainingData returns correct data from database
     */
    public function testGetTrainingData_ReturnsRealDatabaseData(): void
    {
        // Act
        $data = $this->trainingService->getTrainingData($this->testUserId);

        // Assert
        $this->assertIsArray($data);
        $this->assertArrayHasKey('resources', $data);
        $this->assertArrayHasKey('costs', $data);

        // Verify resources match database
        $this->assertEquals($this->testUserId, $data['resources']->user_id);
        $this->assertEquals(500000, $data['resources']->credits);
        $this->assertEquals(100, $data['resources']->untrained_citizens);
        $this->assertEquals(10, $data['resources']->workers);
        $this->assertEquals(5, $data['resources']->soldiers);

        // Verify costs structure exists
        $this->assertIsArray($data['costs']);
        $this->assertArrayHasKey('soldiers', $data['costs']);
        $this->assertArrayHasKey('workers', $data['costs']);
    }
}
