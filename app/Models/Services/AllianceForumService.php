<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Repositories\AllianceForumTopicRepository;
use App\Models\Repositories\AllianceForumPostRepository;
use PDO;
use Throwable;

/**
 * Handles all business logic for the Alliance Forum.
 * * Refactored for Strict Dependency Injection.
 * * Decoupled from Session: Returns ServiceResponse.
 */
class AllianceForumService
{
    private PDO $db;
    private Config $config;
    
    private UserRepository $userRepo;
    private AllianceRoleRepository $roleRepo;
    private AllianceForumTopicRepository $topicRepo;
    private AllianceForumPostRepository $postRepo;

    /**
     * DI Constructor.
     * REMOVED: Session dependency.
     *
     * @param PDO $db
     * @param Config $config
     * @param UserRepository $userRepo
     * @param AllianceRoleRepository $roleRepo
     * @param AllianceForumTopicRepository $topicRepo
     * @param AllianceForumPostRepository $postRepo
     */
    public function __construct(
        PDO $db,
        Config $config,
        UserRepository $userRepo,
        AllianceRoleRepository $roleRepo,
        AllianceForumTopicRepository $topicRepo,
        AllianceForumPostRepository $postRepo
    ) {
        $this->db = $db;
        $this->config = $config;
        
        $this->userRepo = $userRepo;
        $this->roleRepo = $roleRepo;
        $this->topicRepo = $topicRepo;
        $this->postRepo = $postRepo;
    }

    /**
     * Gets all data needed for the main forum index (list of topics).
     */
    public function getForumData(int $allianceId, int $page): ?array
    {
        $perPage = $this->config->get('app.forum.topics_per_page', 25);
        $totalTopics = $this->topicRepo->getCountByAllianceId($allianceId);
        $totalPages = (int)ceil($totalTopics / $perPage);
        $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));
        $offset = ($page - 1) * $perPage;

        $topics = $this->topicRepo->findByAllianceId($allianceId, $perPage, $offset);

        return [
            'topics' => $topics,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages
            ]
        ];
    }

    /**
     * Gets all data needed for viewing a single topic.
     * Returns null if not found or unauthorized (Controller handles 404 logic).
     */
    public function getTopicData(int $topicId, int $viewerAllianceId): ?array
    {
        $topic = $this->topicRepo->findById($topicId);

        // Security check: Ensure topic exists and viewer is in the same alliance
        if (!$topic || $topic->alliance_id !== $viewerAllianceId) {
            return null;
        }

        $posts = $this->postRepo->findAllByTopicId($topicId);

        return [
            'topic' => $topic,
            'posts' => $posts
        ];
    }

    /**
     * Creates a new topic and its first post.
     *
     * @param int $userId
     * @param int $allianceId
     * @param string $title
     * @param string $content
     * @return ServiceResponse
     */
    public function createTopic(int $userId, int $allianceId, string $title, string $content): ServiceResponse
    {
        // 1. Validation
        if (empty(trim($title)) || mb_strlen($title) > 255) {
            return ServiceResponse::error('Title must be between 1 and 255 characters.');
        }
        if (empty(trim($content)) || mb_strlen($content) > 10000) {
            return ServiceResponse::error('Post content must be between 1 and 10,000 characters.');
        }

        // 2. Transaction
        $this->db->beginTransaction();
        try {
            // 2a. Create the topic
            $newTopicId = $this->topicRepo->createTopic($allianceId, $userId, $title);
            
            // 2b. Create the first post
            $this->postRepo->createPost($newTopicId, $allianceId, $userId, $content);
            
            // 2c. Commit
            $this->db->commit();
            
            return ServiceResponse::success('Topic created successfully.', ['topic_id' => $newTopicId]);

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Alliance Topic Creation Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred while creating the topic.');
        }
    }

    /**
     * Creates a new post (reply) in a topic.
     *
     * @param int $userId
     * @param int $topicId
     * @param string $content
     * @return ServiceResponse
     */
    public function createPost(int $userId, int $topicId, string $content): ServiceResponse
    {
        // 1. Get user and topic data
        $user = $this->userRepo->findById($userId);
        $topic = $this->topicRepo->findById($topicId);

        if (!$topic || !$user || $topic->alliance_id !== $user->alliance_id) {
            return ServiceResponse::error('Topic not found.');
        }

        // 2. Validation
        if ($topic->is_locked) {
            return ServiceResponse::error('This topic is locked and cannot be replied to.');
        }
        if (empty(trim($content)) || mb_strlen($content) > 10000) {
            return ServiceResponse::error('Post content must be between 1 and 10,000 characters.');
        }

        // 3. Transaction
        $this->db->beginTransaction();
        try {
            // 3a. Create the post
            $this->postRepo->createPost($topicId, $user->alliance_id, $userId, $content);
            
            // 3b. Update the topic's last reply info
            $this->topicRepo->updateLastReply($topicId, $userId);
            
            $this->db->commit();
            
            return ServiceResponse::success('Reply posted successfully.');

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Alliance Post Creation Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred while posting the reply.');
        }
    }

    /**
     * Toggles the pin status of a topic.
     */
    public function toggleTopicPin(int $adminUserId, int $topicId): ServiceResponse
    {
        $topic = $this->topicRepo->findById($topicId);
        $check = $this->checkModerationPermission($adminUserId, $topic);
        if (!$check['allowed']) {
            return ServiceResponse::error($check['message']);
        }

        $newStatus = !$topic->is_pinned;
        $this->topicRepo->updateTopicStatus($topicId, 'is_pinned', $newStatus);
        
        $msg = 'Topic has been ' . ($newStatus ? 'pinned.' : 'unpinned.');
        return ServiceResponse::success($msg);
    }

    /**
     * Toggles the lock status of a topic.
     */
    public function toggleTopicLock(int $adminUserId, int $topicId): ServiceResponse
    {
        $topic = $this->topicRepo->findById($topicId);
        $check = $this->checkModerationPermission($adminUserId, $topic);
        if (!$check['allowed']) {
            return ServiceResponse::error($check['message']);
        }

        $newStatus = !$topic->is_locked;
        $this->topicRepo->updateTopicStatus($topicId, 'is_locked', $newStatus);
        
        $msg = 'Topic has been ' . ($newStatus ? 'locked.' : 'unlocked.');
        return ServiceResponse::success($msg);
    }

    /**
     * Deletes a post. (Admin only)
     */
    public function deletePost(int $adminUserId, int $postId): ServiceResponse
    {
        $post = $this->postRepo->findById($postId);
        if (!$post) {
            return ServiceResponse::error('Post not found.');
        }
        
        $topic = $this->topicRepo->findById($post->topic_id);
        $check = $this->checkModerationPermission($adminUserId, $topic);
        if (!$check['allowed']) {
            return ServiceResponse::error($check['message']);
        }

        $this->postRepo->deletePost($postId);
        return ServiceResponse::success('Post deleted.');
    }

    /**
     * Helper function to check permissions.
     * Returns array [allowed => bool, message => string].
     */
    private function checkModerationPermission(int $userId, ?\App\Models\Entities\AllianceForumTopic $topic): array
    {
        $user = $this->userRepo->findById($userId);
        
        if (!$user || $user->alliance_id === null) {
            return ['allowed' => false, 'message' => 'You are not in an alliance.'];
        }
        
        if (!$topic || $topic->alliance_id !== $user->alliance_id) {
            return ['allowed' => false, 'message' => 'You do not have permission for this topic.'];
        }

        $role = $this->roleRepo->findById($user->alliance_role_id);
        
        if ($role && $role->can_manage_forum) {
            return ['allowed' => true, 'message' => ''];
        }

        return ['allowed' => false, 'message' => 'You do not have permission to manage the forum.'];
    }
}