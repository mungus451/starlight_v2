<?php

namespace Tests\Unit\Services;

use App\Models\Services\AllianceStructureService;
use App\Models\Services\AlliancePolicyService;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceBankLogRepository;
use App\Models\Repositories\AllianceStructureRepository;
use App\Models\Repositories\AllianceStructureDefinitionRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Entities\User;
use App\Models\Entities\Alliance;
use App\Models\Entities\AllianceRole;
use App\Models\Entities\AllianceStructureDefinition;
use App\Models\Entities\AllianceStructure;
use App\Core\ServiceResponse;
use Tests\Unit\TestCase;
use Mockery;
use PDO;

class AllianceStructureServiceTest extends TestCase
{
    private $db;
    private $allianceRepo;
    private $bankLogRepo;
    private $allianceStructRepo;
    private $structDefRepo;
    private $userRepo;
    private $roleRepo;
    private $policyService;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Mockery::mock(PDO::class);
        $this->allianceRepo = Mockery::mock(AllianceRepository::class);
        $this->bankLogRepo = Mockery::mock(AllianceBankLogRepository::class);
        $this->allianceStructRepo = Mockery::mock(AllianceStructureRepository::class);
        $this->structDefRepo = Mockery::mock(AllianceStructureDefinitionRepository::class);
        $this->userRepo = Mockery::mock(UserRepository::class);
        $this->roleRepo = Mockery::mock(AllianceRoleRepository::class);
        $this->policyService = Mockery::mock(AlliancePolicyService::class);

        $this->service = new AllianceStructureService(
            $this->db,
            $this->allianceRepo,
            $this->bankLogRepo,
            $this->allianceStructRepo,
            $this->structDefRepo,
            $this->userRepo,
            $this->roleRepo,
            $this->policyService
        );
    }

    public function testGetStructurePageDataSuccess(): void
    {
        $userId = 1;
        $allianceId = 10;
        $roleId = 5;

        $user = new User(
            id: $userId,
            email: 'test@example.com',
            characterName: 'TestChar',
            bio: null,
            profile_picture_url: null,
            phone_number: null,
            alliance_id: $allianceId,
            alliance_role_id: $roleId,
            passwordHash: 'hash',
            createdAt: '2025-01-01',
            is_npc: false
        );
        $role = new AllianceRole(
            id: $roleId,
            alliance_id: $allianceId,
            name: 'Leader',
            sort_order: 0,
            can_edit_profile: true,
            can_manage_applications: true,
            can_invite_members: true,
            can_kick_members: true,
            can_manage_roles: true,
            can_see_private_board: true,
            can_manage_forum: true,
            can_manage_bank: true,
            can_manage_structures: true,
            can_manage_diplomacy: true,
            can_declare_war: true
        );
        $alliance = new Alliance(
            id: $allianceId,
            name: 'Test Alliance',
            tag: 'TAG',
            description: '',
            profile_picture_url: '',
            is_joinable: true,
            leader_id: 1,
            net_worth: 0,
            bank_credits: 1000000,
            last_compound_at: null,
            created_at: '2025-01-01'
        );
        
        $def = new AllianceStructureDefinition(
            structure_key: 'comm_nexus',
            name: 'Command Nexus',
            description: 'desc',
            base_cost: 1000,
            cost_multiplier: 2.0,
            bonus_text: 'bonus',
            bonuses_json: '[]'
        );
        $owned = new AllianceStructure(
            id: 1,
            alliance_id: $allianceId,
            structure_key: 'comm_nexus',
            level: 1,
            created_at: '2025-01-01',
            updated_at: '2025-01-01'
        );

        $this->userRepo->shouldReceive('findById')->with($userId)->andReturn($user);
        $this->roleRepo->shouldReceive('findById')->with($roleId)->andReturn($role);
        $this->allianceRepo->shouldReceive('findById')->with($allianceId)->andReturn($alliance);
        $this->structDefRepo->shouldReceive('getAllDefinitions')->andReturn([$def]);
        $this->allianceStructRepo->shouldReceive('findByAllianceId')->with($allianceId)->andReturn(['comm_nexus' => $owned]);

        $response = $this->service->getStructurePageData($userId);

        $this->assertServiceSuccess($response);
        $data = $response->data;
        $this->assertEquals(1, $data['currentLevels']['comm_nexus']);
        $this->assertEquals(2000, $data['costs']['comm_nexus']); // 1000 * 2.0^1
        $this->assertTrue($data['canManage']);
    }

    public function testPurchaseUpgradeFailsOnInsufficientCredits(): void
    {
        $userId = 1;
        $allianceId = 10;
        $roleId = 5;

        $user = new User(
            id: $userId,
            email: 'test@example.com',
            characterName: 'TestChar',
            bio: null,
            profile_picture_url: null,
            phone_number: null,
            alliance_id: $allianceId,
            alliance_role_id: $roleId,
            passwordHash: 'hash',
            createdAt: '2025-01-01',
            is_npc: false
        );
        $role = new AllianceRole(
            id: $roleId,
            alliance_id: $allianceId,
            name: 'Leader',
            sort_order: 0,
            can_edit_profile: true,
            can_manage_applications: true,
            can_invite_members: true,
            can_kick_members: true,
            can_manage_roles: true,
            can_see_private_board: true,
            can_manage_forum: true,
            can_manage_bank: true,
            can_manage_structures: true,
            can_manage_diplomacy: true,
            can_declare_war: true
        );
        $alliance = new Alliance(
            id: $allianceId,
            name: 'Test Alliance',
            tag: 'TAG',
            description: '',
            profile_picture_url: '',
            is_joinable: true,
            leader_id: 1,
            net_worth: 0,
            bank_credits: 500, // Only 500 credits
            last_compound_at: null,
            created_at: '2025-01-01'
        );
        
        $def = new AllianceStructureDefinition(
            structure_key: 'comm_nexus',
            name: 'Command Nexus',
            description: 'desc',
            base_cost: 1000,
            cost_multiplier: 2.0,
            bonus_text: 'bonus',
            bonuses_json: '[]'
        );
        $owned = new AllianceStructure(
            id: 1,
            alliance_id: $allianceId,
            structure_key: 'comm_nexus',
            level: 0,
            created_at: '2025-01-01',
            updated_at: '2025-01-01'
        );

        $this->userRepo->shouldReceive('findById')->with($userId)->andReturn($user);
        $this->roleRepo->shouldReceive('findById')->with($roleId)->andReturn($role);
        $this->structDefRepo->shouldReceive('findByKey')->with('comm_nexus')->andReturn($def);
        $this->allianceRepo->shouldReceive('findById')->with($allianceId)->andReturn($alliance);
        $this->allianceStructRepo->shouldReceive('findByAllianceAndKey')->with($allianceId, 'comm_nexus')->andReturn($owned);

        $response = $this->service->purchaseOrUpgradeStructure($userId, 'comm_nexus');

        $this->assertServiceFailure($response);
        $this->assertServiceMessageContains($response, 'does not have enough credits');
    }

    public function testPurchaseUpgradeSuccess(): void
    {
        $userId = 1;
        $allianceId = 10;
        $roleId = 5;

        $user = new User(
            id: $userId,
            email: 'test@example.com',
            characterName: 'TestChar',
            bio: null,
            profile_picture_url: null,
            phone_number: null,
            alliance_id: $allianceId,
            alliance_role_id: $roleId,
            passwordHash: 'hash',
            createdAt: '2025-01-01',
            is_npc: false
        );
        $role = new AllianceRole(
            id: $roleId,
            alliance_id: $allianceId,
            name: 'Leader',
            sort_order: 0,
            can_edit_profile: true,
            can_manage_applications: true,
            can_invite_members: true,
            can_kick_members: true,
            can_manage_roles: true,
            can_see_private_board: true,
            can_manage_forum: true,
            can_manage_bank: true,
            can_manage_structures: true,
            can_manage_diplomacy: true,
            can_declare_war: true
        );
        $alliance = new Alliance(
            id: $allianceId,
            name: 'Test Alliance',
            tag: 'TAG',
            description: '',
            profile_picture_url: '',
            is_joinable: true,
            leader_id: 1,
            net_worth: 0,
            bank_credits: 5000,
            last_compound_at: null,
            created_at: '2025-01-01'
        );
        
        $def = new AllianceStructureDefinition(
            structure_key: 'comm_nexus',
            name: 'Command Nexus',
            description: 'desc',
            base_cost: 1000,
            cost_multiplier: 2.0,
            bonus_text: 'bonus',
            bonuses_json: '[]'
        );
        $owned = new AllianceStructure(
            id: 1,
            alliance_id: $allianceId,
            structure_key: 'comm_nexus',
            level: 1,
            created_at: '2025-01-01',
            updated_at: '2025-01-01'
        ); // Current level 1
        
        $this->userRepo->shouldReceive('findById')->with($userId)->andReturn($user);
        $this->roleRepo->shouldReceive('findById')->with($roleId)->andReturn($role);
        $this->structDefRepo->shouldReceive('findByKey')->with('comm_nexus')->andReturn($def);
        $this->allianceRepo->shouldReceive('findById')->with($allianceId)->andReturn($alliance);
        $this->allianceStructRepo->shouldReceive('findByAllianceAndKey')->with($allianceId, 'comm_nexus')->andReturn($owned);

        // Transaction expectations
        $this->db->shouldReceive('inTransaction')->andReturn(false);
        $this->db->shouldReceive('beginTransaction')->once();
        $this->db->shouldReceive('commit')->once();

        // Repository expectations
        $this->allianceRepo->shouldReceive('updateBankCreditsRelative')->with($allianceId, -2000)->once();
        $this->bankLogRepo->shouldReceive('createLog')->once();
        $this->allianceStructRepo->shouldReceive('createOrUpgrade')->with($allianceId, 'comm_nexus', 2)->once();

        $response = $this->service->purchaseOrUpgradeStructure($userId, 'comm_nexus');

        $this->assertServiceSuccess($response);
        $this->assertServiceMessageContains($response, 'upgrade to Level 2 complete');
    }

    public function testPurchaseUpgradeUnauthorized(): void
    {
        $userId = 1;
        $allianceId = 10;
        $roleId = 5;

        $user = new User(
            id: $userId,
            email: 'test@example.com',
            characterName: 'TestChar',
            bio: null,
            profile_picture_url: null,
            phone_number: null,
            alliance_id: $allianceId,
            alliance_role_id: $roleId,
            passwordHash: 'hash',
            createdAt: '2025-01-01',
            is_npc: false
        );
        $role = new AllianceRole(
            id: $roleId,
            alliance_id: $allianceId,
            name: 'Member',
            sort_order: 1,
            can_edit_profile: false,
            can_manage_applications: false,
            can_invite_members: false,
            can_kick_members: false,
            can_manage_roles: false,
            can_see_private_board: true,
            can_manage_forum: true,
            can_manage_bank: false,
            can_manage_structures: false, // can_manage_structures = false
            can_manage_diplomacy: false,
            can_declare_war: false
        );
        
        $this->userRepo->shouldReceive('findById')->with($userId)->andReturn($user);
        $this->roleRepo->shouldReceive('findById')->with($roleId)->andReturn($role);

        $response = $this->service->purchaseOrUpgradeStructure($userId, 'comm_nexus');

        $this->assertServiceFailure($response);
        $this->assertServiceMessageContains($response, 'do not have permission');
    }
}
