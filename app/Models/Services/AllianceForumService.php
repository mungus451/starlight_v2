<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Session;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Repositories\AllianceForumTopicRepository;
use App\Models\Repositories\AllianceForumPostRepository;
use App\Models\Services\AlliancePolicyService;
use PDO;
use Throwable;

/**
 * Handles all business logic for the Alliance Forum.
 */
class AllianceForumService
{
    private PDO $db;
    private Session $session;
    private Config $config;
    private UserRepository $userRepo;
    private AllianceRoleRepository $roleRepo;
    private AllianceForumTopicRepository $topicRepo;
    private AllianceForumPostRepository $postRepo;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->session = new Session();
        $this->config = new Config();
        
        $this->userRepo = new UserRepository($this->db);
        $this->roleRepo = new AllianceRoleRepository($this->db);
        $this->topicRepo = new AllianceForumTopicRepository($this->db);
        $this->postRepo = new AllianceForumPostRepository($this->db);
    }

    /**
     * Gets all data needed for the main forum index (list of topics).
     *
     * @param int $allianceId
     * @param int $page
     * @return array|null
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
     *
     * @param int $topicId
     * @param int $viewerAllianceId
     * @return array|null
     */
    public function getTopicData(int $topicId, int $viewerAllianceId): ?array
    {
        $topic = $this->topicRepo->findById($topicId);

        // Security check: Ensure topic exists and viewer is in the same alliance
        if (!$topic || $topic->alliance_id !== $viewerAllianceId) {
            $this->session->setFlash('error', 'Topic not found.');
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
     * @return int|null The new topic ID on success, or null on failure
     */
    public function createTopic(int $userId, int $allianceId, string $title, string $content): ?int
    {
        // 1. Validation
        if (empty(trim($title)) || mb_strlen($title) > 255) {
            $this->session->setFlash('error', 'Title must be between 1 and 255 characters.');
            return null;
        }
        if (empty(trim($content)) || mb_strlen($content) > 10000) {
            $this->session->setFlash('error', 'Post content must be between 1 and 10,000 characters.');
            return null;
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
            
            $this->session->setFlash('success', 'Topic created successfully.');
            return $newTopicId;

        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('Alliance Topic Creation Error: ' . $e->getMessage());
            $this->session->setFlash('error', 'A database error occurred while creating the topic.');
            return null;
        }
    }

    /**
     * Creates a new post (reply) in a topic.
     *
     * @param int $userId
     * @param int $topicId
     * @param string $content
     * @return bool True on success
     */
    public function createPost(int $userId, int $topicId, string $content): bool
    {
        // 1. Get user and topic data
        $user = $this->userRepo->findById($userId);
        $topic = $this->topicRepo->findById($topicId);

        if (!$topic || !$user || $topic->alliance_id !== $user->alliance_id) {
            $this->session->setFlash('error', 'Topic not found.');
            return false;
        }

        // 2. Validation
        if ($topic->is_locked) {
            $this->session->setFlash('error', 'This topic is locked and cannot be replied to.');
            return false;
        }
        if (empty(trim($content)) || mb_strlen($content) > 10000) {
            $this->session->setFlash('error', 'Post content must be between 1 and 10,000 characters.');
            return false;
        }

        // 3. Transaction
        $this->db->beginTransaction();
        try {
            // 3a. Create the post
            $this->postRepo->createPost($topicId, $user->alliance_id, $userId, $content);
            
            // 3b. Update the topic's last reply info
            $this->topicRepo->updateLastReply($topicId, $userId);
            
            $this->db->commit();
            
            $this->session->setFlash('success', 'Reply posted successfully.');
            return true;

        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('Alliance Post Creation Error: ' . $e->getMessage());
            $this->session->setFlash('error', 'A database error occurred while posting the reply.');
            return false;
        }
    }

    /**
     * Toggles the pin status of a topic.
     *
     * @param int $adminUserId
     * @param int $topicId
     * @return bool
     */
    public function toggleTopicPin(int $adminUserId, int $topicId): bool
    {
        $topic = $this->topicRepo->findById($topicId);
        if (!$this->checkModerationPermission($adminUserId, $topic)) {
            return false; // Permission check sets flash
        }

        $newStatus = !$topic->is_pinned;
        $this->topicRepo->updateTopicStatus($topicId, 'is_pinned', $newStatus);
        $this->session->setFlash('success', 'Topic has been ' . ($newStatus ? 'pinned.' : 'unpinned.'));
        return true;
    }

    /**
     * Toggles the lock status of a topic.
     *
     * @param int $adminUserId
     * @param int $topicId
     * @return bool
     */
    public function toggleTopicLock(int $adminUserId, int $topicId): bool
    {
        $topic = $this->topicRepo->findById($topicId);
        if (!$this->checkModerationPermission($adminUserId, $topic)) {
            return false; // Permission check sets flash
        }

        $newStatus = !$topic->is_locked;
        $this->topicRepo->updateTopicStatus($topicId, 'is_locked', $newStatus);
        $this->session->setFlash('success', 'Topic has been ' . ($newStatus ? 'locked.' : 'unlocked.'));
        return true;
    }

    /**
     * Deletes a post. (Admin only)
     *
     * @param int $adminUserId
     * @param int $postId
     * @return bool
     */
    public function deletePost(int $adminUserId, int $postId): bool
    {
        // Note: This is a simple implementation.
        // A full implementation would need to check if it's the *first* post,
        // and if so, delete the entire topic. For now, we just delete the post.
        
        $post = $this->postRepo->findById($postId);
        if (!$post) {
            $this->session->setFlash('error', 'Post not found.');
            return false;
        }
        
        $topic = $this->topicRepo->findById($post->topic_id);
        if (!$this->checkModerationPermission($adminUserId, $topic)) {
            return false;
        }

        $this->postRepo->deletePost($postId);
        $this->session->setFlash('success', 'Post deleted.');
        return true;
    }

    /**
     * Helper function to check if a user has 'can_manage_forum' permission
     * for a specific topic's alliance.
     *
     * @param int $userId
     * @param \App\Models\Entities\AllianceForumTopic|null $topic
     * @return bool
     */
    private function checkModerationPermission(int $userId, ?\App\Models\Entities\AllianceForumTopic $topic): bool
    {
        $user = $this->userRepo->findById($userId);
        
        if (!$user || $user->alliance_id === null) {
            $this->session->setFlash('error', 'You are not in an alliance.');
            return false;
        }
        
        if (!$topic || $topic->alliance_id !== $user->alliance_id) {
            $this->session->setFlash('error', 'You do not have permission for this topic.');
            return false;
        }

        $role = $this->roleRepo->findById($user->alliance_role_id);
        
        if ($role && $role->can_manage_forum) {
            return true;
        }

        $this->session->setFlash('error', 'You do not have permission to manage the forum.');
        return false;
    }
}