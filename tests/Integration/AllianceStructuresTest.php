<?php

namespace Tests\Integration;

use App\Core\Database;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceBankLogRepository;
use App\Models\Repositories\AllianceStructureRepository;
use App\Models\Repositories\AllianceStructureDefinitionRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Services\AllianceStructureService;
use App\Models\Services\AlliancePolicyService;
use PHPUnit\Framework\TestCase;

class AllianceStructuresTest extends TestCase
{
    private $db;
    private $service;
    private $userId;
    private $allianceId;
    private $roleId;

    protected function setUp(): void
    {
        // 1. Database Connection
        $this->db = Database::getInstance();

        // 2. Setup Repositories
        $allianceRepo = new AllianceRepository($this->db);
        $bankLogRepo = new AllianceBankLogRepository($this->db);
        $structRepo = new AllianceStructureRepository($this->db);
        $defRepo = new AllianceStructureDefinitionRepository($this->db);
        $userRepo = new UserRepository($this->db);
        $roleRepo = new AllianceRoleRepository($this->db);
        $policyService = new AlliancePolicyService($this->db, $roleRepo);

        // 3. Setup Service
        $this->service = new AllianceStructureService(
            $this->db,
            $allianceRepo,
            $bankLogRepo,
            $structRepo,
            $defRepo,
            $userRepo,
            $roleRepo,
            $policyService
        );

        // 4. Create Test Data
        // User
        $stmt = $this->db->prepare("INSERT INTO users (email, password_hash, character_name) VALUES (?, ?, ?)");
        $stmt->execute(['test_astruct_' . uniqid() . '@example.com', 'hash', 'TestAStruct_' . uniqid()]);
        $this->userId = (int)$this->db->lastInsertId();

        // Alliance
        $uniqueTag = substr('T' . uniqid(), 0, 5); // Ensure unique tag max 5 chars
        $stmt = $this->db->prepare("INSERT INTO alliances (name, tag, leader_id, bank_credits) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Test Alliance ' . uniqid(), $uniqueTag, $this->userId, 1000000000]); // 1 Billion Credits
        $this->allianceId = (int)$this->db->lastInsertId();

        // Role (Admin)
        $stmt = $this->db->prepare("INSERT INTO alliance_roles (alliance_id, name, can_manage_structures) VALUES (?, ?, ?)");
        $stmt->execute([$this->allianceId, 'Structure Master', 1]);
        $this->roleId = (int)$this->db->lastInsertId();

        // Link User to Alliance & Role
        $stmt = $this->db->prepare("UPDATE users SET alliance_id = ?, alliance_role_id = ? WHERE id = ?");
        $stmt->execute([$this->allianceId, $this->roleId, $this->userId]);
    }

    protected function tearDown(): void
    {
        // Clean up in reverse order of dependencies
        if ($this->userId && $this->allianceId) {
            // Break circular dependency: User -> Alliance
            $this->db->exec("UPDATE users SET alliance_id = NULL, alliance_role_id = NULL WHERE id = {$this->userId}");

            $this->db->exec("DELETE FROM alliance_bank_logs WHERE alliance_id = {$this->allianceId}");
            $this->db->exec("DELETE FROM alliance_structures WHERE alliance_id = {$this->allianceId}");
            $this->db->exec("DELETE FROM alliance_roles WHERE alliance_id = {$this->allianceId}");
            
            // Delete Alliance first (now safe because users don't point to it)
            // But wait, Alliance points to Leader (User). 
            // We must delete Alliance first? No, Alliance.leader_id -> Users.id.
            // If we delete User, Alliance complains.
            // If we delete Alliance, User complains (if User.alliance_id has FK).
            // We just NULLed User.alliance_id. So we can delete Alliance.
            $this->db->exec("DELETE FROM alliances WHERE id = {$this->allianceId}");
            
            // Finally delete User
            $this->db->exec("DELETE FROM users WHERE id = {$this->userId}");
        }
    }

    public function testPurchaseNewStructure(): void
    {
        $structureKey = 'citadel_shield'; // Assuming this exists in your seed
        
        // Action: Buy Level 1
        $response = $this->service->purchaseOrUpgradeStructure($this->userId, $structureKey);
        
        $this->assertTrue($response->isSuccess(), 'Purchase failed: ' . $response->message);
        
        // Verify Database
        $stmt = $this->db->prepare("SELECT level FROM alliance_structures WHERE alliance_id = ? AND structure_key = ?");
        $stmt->execute([$this->allianceId, $structureKey]);
        $level = $stmt->fetchColumn();
        
        $this->assertEquals(1, $level, "Structure level should be 1 after purchase");
    }

    public function testUpgradeStructure(): void
    {
        $structureKey = 'citadel_shield';
        
        // 1. Buy Level 1
        $this->service->purchaseOrUpgradeStructure($this->userId, $structureKey);
        
        // 2. Upgrade to Level 2
        $response = $this->service->purchaseOrUpgradeStructure($this->userId, $structureKey);
        
        $this->assertTrue($response->isSuccess(), 'Upgrade failed: ' . $response->message);
        
        // Verify Database
        $stmt = $this->db->prepare("SELECT level FROM alliance_structures WHERE alliance_id = ? AND structure_key = ?");
        $stmt->execute([$this->allianceId, $structureKey]);
        $level = $stmt->fetchColumn();
        
        $this->assertEquals(2, $level, "Structure level should be 2 after upgrade");
    }

    public function testBankBalanceDeducted(): void
    {
        $structureKey = 'citadel_shield';
        
        // Get initial balance (should be 1 billion)
        $stmt = $this->db->prepare("SELECT bank_credits FROM alliances WHERE id = ?");
        $stmt->execute([$this->allianceId]);
        $initialBalance = $stmt->fetchColumn();
        
        // Buy Level 1
        $this->service->purchaseOrUpgradeStructure($this->userId, $structureKey);
        
        // Get new balance
        $stmt->execute([$this->allianceId]);
        $newBalance = $stmt->fetchColumn();
        
        $this->assertLessThan($initialBalance, $newBalance, "Bank credits should decrease");
        
        // Verify Log
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM alliance_bank_logs WHERE alliance_id = ? AND log_type = 'structure_purchase'");
        $stmt->execute([$this->allianceId]);
        $count = $stmt->fetchColumn();
        
        $this->assertEquals(1, $count, "Should have 1 bank log entry");
    }

    public function testInsufficientFunds(): void
    {
        // Set bank to 0
        $this->db->prepare("UPDATE alliances SET bank_credits = 0 WHERE id = ?")->execute([$this->allianceId]);
        
        $structureKey = 'citadel_shield';
        
        $response = $this->service->purchaseOrUpgradeStructure($this->userId, $structureKey);
        
        $this->assertFalse($response->isSuccess(), "Should fail with insufficient funds");
        $this->assertStringContainsString('not have enough credits', $response->message);
    }
}