<?php

namespace Tests\Integration;

use App\Core\Database;
use App\Models\Repositories\StructureRepository;
use PHPUnit\Framework\TestCase;

class StructureRepositoryTest extends TestCase
{
    private $db;
    private $repo;
    private $userId;

    protected function setUp(): void
    {
        // Assume Database::getConnection() returns a PDO instance connected to the test DB
        $this->db = Database::getInstance();
        $this->repo = new StructureRepository($this->db);

        // Setup a user for testing
        $stmt = $this->db->prepare("INSERT INTO users (email, password_hash, character_name) VALUES (?, ?, ?)");
        $stmt->execute(['test_struct_' . uniqid() . '@example.com', 'hash', 'TestChar_' . uniqid()]);
        $this->userId = (int)$this->db->lastInsertId();
    }

    protected function tearDown(): void
    {
        // Cleanup
        $this->db->exec("DELETE FROM user_structures WHERE user_id = {$this->userId}");
        $this->db->exec("DELETE FROM users WHERE id = {$this->userId}");
    }

    public function testCreateDefaultsAndFind()
    {
        $this->repo->createDefaults($this->userId);
        
        $structure = $this->repo->findByUserId($this->userId);
        
        $this->assertNotNull($structure);
        $this->assertEquals($this->userId, $structure->user_id);
        $this->assertEquals(0, $structure->economy_upgrade_level);
        $this->assertEquals(0, $structure->population_level);
    }

    public function testUpdateStructureLevel()
    {
        $this->repo->createDefaults($this->userId);

        $success = $this->repo->updateStructureLevel($this->userId, 'economy_upgrade_level', 5);
        $this->assertTrue($success);

        $structure = $this->repo->findByUserId($this->userId);
        $this->assertEquals(5, $structure->economy_upgrade_level);
    }

    public function testUpdateStructureLevelSecurity()
    {
        $this->repo->createDefaults($this->userId);

        // Try to update a non-whitelisted column (e.g., injection attempt)
        $success = $this->repo->updateStructureLevel($this->userId, 'user_id', 999);
        
        $this->assertFalse($success);

        // Verify it wasn't changed
        $structure = $this->repo->findByUserId($this->userId);
        $this->assertEquals($this->userId, $structure->user_id);
    }

    public function testUpdateNewStructures()
    {
        $this->repo->createDefaults($this->userId);

        $success = $this->repo->updateStructureLevel($this->userId, 'population_level', 2);
        $this->assertTrue($success, "Failed to update population_level");
        
        $structure = $this->repo->findByUserId($this->userId);
        $this->assertEquals(2, $structure->population_level);
    }
}
