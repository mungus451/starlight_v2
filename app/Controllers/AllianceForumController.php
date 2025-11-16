<?php

namespace App\Controllers;

use App\Models\Services\AllianceForumService;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Core\Database;

/**
 * Handles all HTTP requests for the Alliance Forum.
 */
class AllianceForumController extends BaseController
{
    private AllianceForumService $forumService;
    private UserRepository $userRepo;
    private AllianceRoleRepository $roleRepo;

    public function __construct()
    {
        parent::__construct();
        $this->forumService = new AllianceForumService();
        
        $db = Database::getInstance();
        $this->userRepo = new UserRepository($db);
        $this->roleRepo = new AllianceRoleRepository($db);
    }

    /**
     * Gets the viewer's user and alliance role.
     * Redirects if the user is not in an alliance.
     * @return array|null [user, role, allianceId] or null on failure
     */
    private function getViewerData(): ?array
    {
        $userId = $this->session->get('user_id');
        $user = $this->userRepo->findById($userId);
        
        if ($user === null || $user->alliance_id === null) {
            $this->session->setFlash('error', 'You must be in an alliance to view the forum.');
            $this->redirect('/alliance/list');
            return null;
        }
        
        $role = $this->roleRepo->findById($user->alliance_role_id);
        
        return [
            'user' => $user,
            'role' => $role,
            'allianceId' => $user->alliance_id
        ];
    }

    /**
     * Displays the main forum index (list of topics).
     */
    public function showForum(array $vars): void
    {
        $viewerData = $this->getViewerData();
        if ($viewerData === null) return;
        
        $page = (int)($vars['page'] ?? 1);
        $data = $this->forumService->getForumData($viewerData['allianceId'], $page);

        $data['layoutMode'] = 'full';
        $data['allianceId'] = $viewerData['allianceId']; // Pass for "Back" button
        $data['canManageForum'] = $viewerData['role'] && $viewerData['role']->can_manage_forum;

        $this->render('alliance/forum_index.php', $data + ['title' => 'Alliance Forum']);
    }

    /**
     * Displays a single topic and its posts.
     */
    public function showTopic(array $vars): void
    {
        $viewerData = $this->getViewerData();
        if ($viewerData === null) return;
        
        $topicId = (int)($vars['id'] ?? 0);
        $data = $this->forumService->getTopicData($topicId, $viewerData['allianceId']);

        if ($data === null) {
            $this->redirect('/alliance/forum'); // Service sets flash error
            return;
        }

        $data['layoutMode'] = 'full';
        $data['allianceId'] = $viewerData['allianceId'];
        $data['canManageForum'] = $viewerData['role'] && $viewerData['role']->can_manage_forum;

        $this->render('alliance/forum_topic.php', $data + ['title' => $data['topic']->title]);
    }

    /**
     * Displays the form to create a new topic.
     */
    public function showCreateTopic(): void
    {
        $viewerData = $this->getViewerData();
        if ($viewerData === null) return;

        $this->render('alliance/forum_create.php', [
            'title' => 'Create New Topic',
            'layoutMode' => 'full',
            'allianceId' => $viewerData['allianceId']
        ]);
    }

    /**
     * Handles the "Create New Topic" form submission.
     */
    public function handleCreateTopic(): void
    {
        $viewerData = $this->getViewerData();
        if ($viewerData === null) return;
        
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/forum/create');
            return;
        }

        $title = (string)($_POST['title'] ?? '');
        $content = (string)($_POST['content'] ?? '');

        $newTopicId = $this->forumService->createTopic($viewerData['user']->id, $viewerData['allianceId'], $title, $content);

        if ($newTopicId !== null) {
            $this->redirect('/alliance/forum/topic/' . $newTopicId);
        } else {
            $this->redirect('/alliance/forum/create'); // Service sets flash error
        }
    }

    /**
     * Handles the "Reply to Topic" form submission.
     */
    public function handleCreatePost(array $vars): void
    {
        $viewerData = $this->getViewerData();
        if ($viewerData === null) return;
        
        $topicId = (int)($vars['id'] ?? 0);
        
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/forum/topic/' . $topicId);
            return;
        }

        $content = (string)($_POST['content'] ?? '');

        $this->forumService->createPost($viewerData['user']->id, $topicId, $content);
        
        // Redirect back to the topic
        $this->redirect('/alliance/forum/topic/' . $topicId);
    }

    /**
     * Handles a moderation request to pin/unpin a topic.
     */
    public function handlePinTopic(array $vars): void
    {
        $viewerData = $this->getViewerData();
        if ($viewerData === null) return;

        $topicId = (int)($vars['id'] ?? 0);
        $this->forumService->toggleTopicPin($viewerData['user']->id, $topicId);
        $this->redirect('/alliance/forum/topic/' . $topicId);
    }

    /**
     * Handles a moderation request to lock/unlock a topic.
     */
    public function handleLockTopic(array $vars): void
    {
        $viewerData = $this->getViewerData();
        if ($viewerData === null) return;

        $topicId = (int)($vars['id'] ?? 0);
        $this->forumService->toggleTopicLock($viewerData['user']->id, $topicId);
        $this->redirect('/alliance/forum/topic/' . $topicId);
    }
}