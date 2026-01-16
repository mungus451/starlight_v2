<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\Permissions;
use App\Core\ServiceResponse;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Repositories\AllianceForumTopicRepository;
use App\Models\Repositories\AllianceForumPostRepository;
use App\Models\Services\NotificationService;
use PDO;
use Throwable;

/**
 * Handles all business logic for the Alliance Forum.
 * * Refactored for strict MVC Compliance (Phase 1.1).
 * * Now handles User/Role lookups internally to prevent Controller logic leaks.
 */
class AllianceForumService
{
    private PDO $db;
    private Config $config;
    
    private UserRepository $userRepo;
    private AllianceRoleRepository $roleRepo;
    private AllianceForumTopicRepository $topicRepo;
    private AllianceForumPostRepository $postRepo;
    private NotificationService $notificationService;

    public function __construct(
        PDO $db,
        Config $config,
        UserRepository $userRepo,
        AllianceRoleRepository $roleRepo,
        AllianceForumTopicRepository $topicRepo,
        AllianceForumPostRepository $postRepo,
        NotificationService $notificationService
    ) {
        $this->db = $db;
        $this->config = $config;
        $this->userRepo = $userRepo;
        $this->roleRepo = $roleRepo;
        $this->topicRepo = $topicRepo;
        $this->postRepo = $postRepo;
        $this->notificationService = $notificationService;
    }

    /**
     * Retrieves all data required for the Forum Index page.
     * Performs authorization checks internally.
     *
     * @param int $userId
     * @param int $page
     * @return ServiceResponse Contains 'topics', 'pagination', 'allianceId', 'canManageForum'
     */
    public function getForumIndexData(int $userId, int $page): ServiceResponse
    {
        // 1. Validate User & Alliance Membership
        $user = $this->userRepo->findById($userId);
        if (!$user || $user->alliance_id === null) {
            return ServiceResponse::error('You must be in an alliance to view the forum.');
        }

        // 2. Determine Permissions
        $role = $this->roleRepo->findById($user->alliance_role_id);
        
        // 3. Fetch Topics
        $perPage = $this->config->get('app.forum.topics_per_page', 25);
        $totalTopics = $this->topicRepo->getCountByAllianceId($user->alliance_id);
        $totalPages = (int)ceil($totalTopics / $perPage);
        $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));
        $offset = ($page - 1) * $perPage;

        $topics = $this->topicRepo->findByAllianceId($user->alliance_id, $perPage, $offset);

        // 4. Return Data Payload
        return ServiceResponse::success('Data retrieved', [
            'topics' => $topics,
            'allianceId' => $user->alliance_id,
            'permissions' => $role, // Pass the whole role object as permissions
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages
            ]
        ]);
    }

    /**
     * Retrieves all data required for the Single Topic View.
     * Performs authorization checks internally.
     *
     * @param int $userId
     * @param int $topicId
     * @return ServiceResponse Contains 'topic', 'posts', 'allianceId', 'canManageForum'
     */
    public function getTopicDetails(int $userId, int $topicId): ServiceResponse
    {
        // 1. Validate User
        $user = $this->userRepo->findById($userId);
        if (!$user || $user->alliance_id === null) {
            return ServiceResponse::error('You must be in an alliance.');
        }

        // 2. Validate Topic & Access
        $topic = $this->topicRepo->findById($topicId);
        if (!$topic || $topic->alliance_id !== $user->alliance_id) {
            return ServiceResponse::error('Topic not found or access denied.');
        }

        // 3. Determine Permissions
        $role = $this->roleRepo->findById($user->alliance_role_id);
        $canManage = ($role && $role->hasPermission(Permissions::CAN_MANAGE_FORUM));

        // 4. Fetch Posts
        $posts = $this->postRepo->findAllByTopicId($topicId);

        return ServiceResponse::success('Data retrieved', [
            'topic' => $topic,
            'posts' => $posts,
            'allianceId' => $user->alliance_id,
            'canManageForum' => $canManage
        ]);
    }

    /**
     * Creates a new topic and its first post.
     */
    public function createTopic(int $userId, string $title, string $content): ServiceResponse
    {
        // 1. Validate User
        $user = $this->userRepo->findById($userId);
        if (!$user || $user->alliance_id === null) {
            return ServiceResponse::error('You must be in an alliance.');
        }

        // 2. Validation Input
        if (empty(trim($title)) || mb_strlen($title) > 255) {
            return ServiceResponse::error('Title must be between 1 and 255 characters.');
        }
        if (empty(trim($content)) || mb_strlen($content) > 10000) {
            return ServiceResponse::error('Post content must be between 1 and 10,000 characters.');
        }

        // 3. Transaction
        $this->db->beginTransaction();
        try {
            $newTopicId = $this->topicRepo->createTopic($user->alliance_id, $userId, $title);
            $this->postRepo->createPost($newTopicId, $user->alliance_id, $userId, $content);
            
            $this->db->commit();
            
            // Send notifications to all alliance members after successful commit (except the topic creator)
            $this->notificationService->notifyAllianceMembers(
                $user->alliance_id,
                $userId,
                'New Forum Topic',
                "{$user->characterName} created topic \"{$title}\"",
                "/alliance/forum/topic/{$newTopicId}"
            );
            
            return ServiceResponse::success('Topic created successfully.', ['topic_id' => $newTopicId]);

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Alliance Topic Creation Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred.');
        }
    }

    /**
     * Creates a new post (reply) in a topic.
     */
    public function createPost(int $userId, int $topicId, string $content): ServiceResponse
    {
        // 1. Get user and topic data
        $user = $this->userRepo->findById($userId);
        $topic = $this->topicRepo->findById($topicId);

        if (!$topic || !$user || $topic->alliance_id !== $user->alliance_id) {
            return ServiceResponse::error('Topic not found.');
        }

        if ($topic->is_locked) {
            return ServiceResponse::error('This topic is locked.');
        }
        if (empty(trim($content)) || mb_strlen($content) > 10000) {
            return ServiceResponse::error('Post content must be between 1 and 10,000 characters.');
        }

        $this->db->beginTransaction();
        try {
            $this->postRepo->createPost($topicId, $user->alliance_id, $userId, $content);
            $this->topicRepo->updateLastReply($topicId, $userId);
            
            $this->db->commit();
            
            // Get users who have participated in this topic and send notifications after successful commit
            $participantIds = $this->postRepo->getTopicParticipantIds($topicId);
            
            // Send notifications only to users who have participated in the topic (except the poster)
            $this->notificationService->notifySpecificUsers(
                $participantIds,
                $userId,
                'New Forum Post',
                "{$user->characterName} posted in \"{$topic->title}\"",
                "/alliance/forum/topic/{$topicId}"
            );
            
            return ServiceResponse::success('Reply posted successfully.');
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('Alliance Post Creation Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred.');
        }
    }

    /**
     * Toggles the pin status of a topic.
     */
    public function toggleTopicPin(int $adminUserId, int $topicId): ServiceResponse
    {
        $topic = $this->topicRepo->findById($topicId);
        $check = $this->checkModerationPermission($adminUserId, $topic);
        if (!$check['allowed']) return ServiceResponse::error($check['message']);

        $newStatus = !$topic->is_pinned;
        $this->topicRepo->updateTopicStatus($topicId, 'is_pinned', $newStatus);
        
        return ServiceResponse::success('Topic has been ' . ($newStatus ? 'pinned.' : 'unpinned.'));
    }

    /**
     * Toggles the lock status of a topic.
     */
    public function toggleTopicLock(int $adminUserId, int $topicId): ServiceResponse
    {
        $topic = $this->topicRepo->findById($topicId);
        $check = $this->checkModerationPermission($adminUserId, $topic);
        if (!$check['allowed']) return ServiceResponse::error($check['message']);

        $newStatus = !$topic->is_locked;
        $this->topicRepo->updateTopicStatus($topicId, 'is_locked', $newStatus);
        
        return ServiceResponse::success('Topic has been ' . ($newStatus ? 'locked.' : 'unlocked.'));
    }

    /**
     * Helper function to check permissions.
     */
    private function checkModerationPermission(int $userId, ?\App\Models\Entities\AllianceForumTopic $topic): array
    {
        $user = $this->userRepo->findById($userId);
        
        if (!$user || $user->alliance_id === null) return ['allowed' => false, 'message' => 'You are not in an alliance.'];
        if (!$topic || $topic->alliance_id !== $user->alliance_id) return ['allowed' => false, 'message' => 'Access denied.'];

        $role = $this->roleRepo->findById($user->alliance_role_id);
        if ($role && $role->hasPermission(Permissions::CAN_MANAGE_FORUM)) return ['allowed' => true, 'message' => ''];

        return ['allowed' => false, 'message' => 'Permission denied.'];
    }
    
    // --- Added for View Logic support ---
    public function getUserAllianceId(int $userId): ?int 
    {
        $user = $this->userRepo->findById($userId);
        return $user ? $user->alliance_id : null;
    }
}