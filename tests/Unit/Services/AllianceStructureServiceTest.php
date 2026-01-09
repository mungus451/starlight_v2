<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use Mockery;
use App\Models\Services\AllianceStructureService;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceBankLogRepository;
use App\Models\Repositories\AllianceStructureRepository;
use App\Models\Repositories\AllianceStructureDefinitionRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Services\AlliancePolicyService;
use App\Models\Services\NotificationService;
use App\Core\Permissions;
use App\Models\Entities\User;
use App\Models\Entities\Alliance;
use App\Models\Entities\AllianceRole;
use App\Models\Entities\AllianceStructureDefinition;
use App\Models\Entities\AllianceStructure;
use PDO;

class AllianceStructureServiceTest extends TestCase
{
    private $db;
    private $allianceRepo;
    private $bankLogRepo;
    private $structRepo;
    private $defRepo;
    private $userRepo;
    private $roleRepo;
    private $policyService;
    private $notificationService;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->createMockPDO();
        $this->db->shouldReceive('inTransaction')->andReturn(false);
        $this->db->shouldReceive('beginTransaction')->andReturn(true);
        $this->db->shouldReceive('commit')->andReturn(true);
        $this->db->shouldReceive('rollBack')->andReturn(true);

        $this->allianceRepo = Mockery::mock(AllianceRepository::class);
        $this->bankLogRepo = Mockery::mock(AllianceBankLogRepository::class);
        $this->structRepo = Mockery::mock(AllianceStructureRepository::class);
        $this->defRepo = Mockery::mock(AllianceStructureDefinitionRepository::class);
        $this->userRepo = Mockery::mock(UserRepository::class);
        $this->roleRepo = Mockery::mock(AllianceRoleRepository::class);
        $this->policyService = Mockery::mock(AlliancePolicyService::class);
        $this->notificationService = Mockery::mock(NotificationService::class);

        $this->service = new AllianceStructureService(
            $this->db,
            $this->allianceRepo,
            $this->bankLogRepo,
            $this->structRepo,
            $this->defRepo,
            $this->userRepo,
            $this->roleRepo,
            $this->policyService,
            $this->notificationService
        );
    }

    public function testPurchaseOrUpgradeStructureHandlesLargeNumbers()
    {
        $userId = 1;
        $allianceId = 100;
        $roleId = 5;
        $structureKey = 'warlords_throne';
        
        // 1. Mock User (Admin)
        $user = new User(
            $userId, 'admin@test.com', 'AdminUser', null, null, null,
            $allianceId, $roleId, 'hash', '2025-01-01', false
        );
        $this->userRepo->shouldReceive('findById')->with($userId)->andReturn($user);
        
        // 2. Mock Role (Can manage)
        $role = new AllianceRole(
            $roleId, $allianceId, 'Leader', 1,
            0xFFFFFFFF // All permissions
        );
        $this->roleRepo->shouldReceive('findById')->with($roleId)->andReturn($role);
        
        // 3. Mock Structure Definition
        $def = new AllianceStructureDefinition(
            $structureKey, "Warlord's Throne", 'Desc', 500000000, 2.5, 'Bonus', '[]'
        );
        $this->defRepo->shouldReceive('findByKey')->with($structureKey)->andReturn($def);
        
        // 4. Mock Alliance (High Bank Credits)
        // Level 27 cost is ~1.11e19. Need > 1.11e19. 
        $highCredits = 2.0e19;
        $alliance = new Alliance(
            $allianceId, 'Name', 'TAG', null, null, false, $userId, 0, 
            $highCredits, 
            null, '2025-01-01'
        );
        $this->allianceRepo->shouldReceive('findById')->with($allianceId)->andReturn($alliance);
        
        // 5. Mock Owned Structure (Level 26)
        $owned = new AllianceStructure(1, $allianceId, $structureKey, 26, null, null);
        $this->structRepo->shouldReceive('findByAllianceAndKey')->with($allianceId, $structureKey)->andReturn($owned);
        
        // 6. Expect Bank Update
        // Cost: 500,000,000 * 2.5^26 = ~1.11e19
        $this->allianceRepo->shouldReceive('updateBankCreditsRelative')
            ->with($allianceId, Mockery::on(function ($amount) {
                // Must be a negative float
                return is_float($amount) && $amount < -1.0e19;
            }))
            ->once()
            ->andReturn(true);
            
        // 7. Expect Log
        $this->bankLogRepo->shouldReceive('createLog')
            ->with($allianceId, $userId, 'structure_purchase', Mockery::type('float'), Mockery::any())
            ->once()
            ->andReturn(true);
            
        // 8. Expect Structure Upgrade
        $this->structRepo->shouldReceive('createOrUpgrade')
            ->with($allianceId, $structureKey, 27)
            ->once()
            ->andReturn(true);
            
        // 9. Expect Notification
        $this->notificationService->shouldReceive('notifyAllianceMembers')
            ->once();
            
        // Execute
        $response = $this->service->purchaseOrUpgradeStructure($userId, $structureKey);
        
        $this->assertServiceSuccess($response);
    }
}
