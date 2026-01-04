<?php

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use App\Models\Services\AllianceForumService;
use App\Models\Services\AllianceStructureService;
use App\Models\Services\NotificationService;
use App\Models\Services\AlliancePolicyService;
use App\Core\Config;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Repositories\AllianceForumTopicRepository;
use App\Models\Repositories\AllianceForumPostRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceBankLogRepository;
use App\Models\Repositories\AllianceStructureRepository;
use App\Models\Repositories\AllianceStructureDefinitionRepository;
use App\Models\Entities\User;
use App\Models\Entities\AllianceForumTopic;
use App\Models\Entities\AllianceStructureDefinition;
use App\Models\Entities\Alliance;
use Mockery;
use PDO;

/**
 * Tests for alliance notification functionality.
 * Verifies that forum posts and structure purchases trigger notifications.
 */
class AllianceNotificationTest extends TestCase
{
    private AllianceForumService $forumService;
    private AllianceStructureService $structureService;
    
    private PDO|Mockery\MockInterface $mockDb;
    private Config|Mockery\MockInterface $mockConfig;
    private UserRepository|Mockery\MockInterface $mockUserRepo;
    private NotificationService|Mockery\MockInterface $mockNotificationService;
    private AllianceRoleRepository|Mockery\MockInterface $mockRoleRepo;
    private AllianceForumTopicRepository|Mockery\MockInterface $mockTopicRepo;
    private AllianceForumPostRepository|Mockery\MockInterface $mockPostRepo;
    private AllianceRepository|Mockery\MockInterface $mockAllianceRepo;
    private AllianceBankLogRepository|Mockery\MockInterface $mockBankLogRepo;
    private AllianceStructureRepository|Mockery\MockInterface $mockStructureRepo;
    private AllianceStructureDefinitionRepository|Mockery\MockInterface $mockStructDefRepo;
    private AlliancePolicyService|Mockery\MockInterface $mockPolicyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockDb = Mockery::mock(PDO::class);
        $this->mockConfig = Mockery::mock(Config::class);
        $this->mockUserRepo = Mockery::mock(UserRepository::class);
        $this->mockNotificationService = Mockery::mock(NotificationService::class);
        $this->mockRoleRepo = Mockery::mock(AllianceRoleRepository::class);
        $this->mockTopicRepo = Mockery::mock(AllianceForumTopicRepository::class);
        $this->mockPostRepo = Mockery::mock(AllianceForumPostRepository::class);
        $this->mockAllianceRepo = Mockery::mock(AllianceRepository::class);
        $this->mockBankLogRepo = Mockery::mock(AllianceBankLogRepository::class);
        $this->mockStructureRepo = Mockery::mock(AllianceStructureRepository::class);
        $this->mockStructDefRepo = Mockery::mock(AllianceStructureDefinitionRepository::class);
        $this->mockPolicyService = Mockery::mock(AlliancePolicyService::class);

        $this->forumService = new AllianceForumService(
            $this->mockDb,
            $this->mockConfig,
            $this->mockUserRepo,
            $this->mockRoleRepo,
            $this->mockTopicRepo,
            $this->mockPostRepo,
            $this->mockNotificationService
        );

        $this->structureService = new AllianceStructureService(
            $this->mockDb,
            $this->mockAllianceRepo,
            $this->mockBankLogRepo,
            $this->mockStructureRepo,
            $this->mockStructDefRepo,
            $this->mockUserRepo,
            $this->mockRoleRepo,
            $this->mockPolicyService,
            $this->mockNotificationService
        );
    }

    /**
     * Test that creating a forum post sends notifications to topic participants except the poster.
     */
    public function testForumPostSendsNotificationsToAllianceMembers(): void
    {
        $posterId = 1;
        $allianceId = 10;
        $topicId = 5;

        $poster = $this->createMockUser($posterId, 'PostAuthor', $allianceId);

        $topic = new AllianceForumTopic(
            id: $topicId,
            alliance_id: $allianceId,
            user_id: 1,
            title: 'Test Topic',
            is_pinned: false,
            is_locked: false,
            created_at: '2024-01-01 00:00:00',
            last_reply_at: '2024-01-01 00:00:00',
            last_reply_user_id: 1
        );

        $this->mockUserRepo->shouldReceive('findById')
            ->with($posterId)
            ->andReturn($poster);

        $this->mockTopicRepo->shouldReceive('findById')
            ->with($topicId)
            ->andReturn($topic);

        $this->mockPostRepo->shouldReceive('createPost')
            ->once()
            ->andReturn(true);

        $this->mockTopicRepo->shouldReceive('updateLastReply')
            ->once()
            ->andReturn(true);

        // Mock getTopicParticipantIds to return some participant IDs
        $this->mockPostRepo->shouldReceive('getTopicParticipantIds')
            ->with($topicId)
            ->once()
            ->andReturn([2, 3, 5]); // Sample participant IDs

        // Expect the NotificationService.notifySpecificUsers to be called once (not notifyAllianceMembers)
        $this->mockNotificationService->shouldReceive('notifySpecificUsers')
            ->with([2, 3, 5], $posterId, 'New Forum Post', Mockery::any(), "/alliance/forum/topic/{$topicId}")
            ->once();

        // Mock transaction
        $this->mockDb->shouldReceive('beginTransaction')->once();
        $this->mockDb->shouldReceive('commit')->once();
        $this->mockDb->shouldReceive('inTransaction')->andReturn(false, true);

        $response = $this->forumService->createPost($posterId, $topicId, 'Test content for post');

        $this->assertTrue($response->isSuccess());
    }

    /**
     * Test that creating a forum topic sends notifications to all alliance members except the creator.
     */
    public function testForumTopicCreationSendsNotificationsToAllianceMembers(): void
    {
        $creatorId = 1;
        $allianceId = 10;
        $topicId = 100;

        $creator = $this->createMockUser($creatorId, 'TopicCreator', $allianceId);

        $this->mockUserRepo->shouldReceive('findById')
            ->with($creatorId)
            ->andReturn($creator);

        $this->mockTopicRepo->shouldReceive('createTopic')
            ->with($allianceId, $creatorId, 'New Test Topic')
            ->once()
            ->andReturn($topicId);

        $this->mockPostRepo->shouldReceive('createPost')
            ->with($topicId, $allianceId, $creatorId, 'First post content')
            ->once()
            ->andReturn(true);

        // Expect the NotificationService.notifyAllianceMembers to be called once for topic creation
        $this->mockNotificationService->shouldReceive('notifyAllianceMembers')
            ->with($allianceId, $creatorId, 'New Forum Topic', Mockery::any(), "/alliance/forum/topic/{$topicId}")
            ->once();

        // Mock transaction
        $this->mockDb->shouldReceive('beginTransaction')->once();
        $this->mockDb->shouldReceive('commit')->once();
        $this->mockDb->shouldReceive('inTransaction')->andReturn(false);

        $response = $this->forumService->createTopic($creatorId, 'New Test Topic', 'First post content');

        $this->assertTrue($response->isSuccess());
        $this->assertEquals($topicId, $response->data['topic_id']);
    }

    /**
     * Helper to create a mock user entity.
     */
    private function createMockUser(int $id, string $name, ?int $allianceId = null): User
    {
        return new User(
            id: $id,
            email: "{$name}@test.com",
            characterName: $name,
            bio: null,
            profile_picture_url: null,
            phone_number: null,
            alliance_id: $allianceId,
            alliance_role_id: 1,
            passwordHash: 'hash',
            createdAt: '2024-01-01 00:00:00',
            is_npc: false
        );
    }
}
