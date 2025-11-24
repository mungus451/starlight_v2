<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\AllianceForumService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRoleRepository;

/**
 * Handles all HTTP requests for the Alliance Forum.
 * * Refactored to consume ServiceResponse objects.
 */
class AllianceForumController extends BaseController
{
    private AllianceForumService $forumService;
    private UserRepository $userRepo;
    private AllianceRoleRepository $roleRepo;

    /**
     * DI Constructor.
     *
     * @param AllianceForumService $forumService
     * @param UserRepository $userRepo
     * @param AllianceRoleRepository $roleRepo
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        AllianceForumService $forumService,
        UserRepository $userRepo,
        AllianceRoleRepository $roleRepo,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $validator, $levelCalculator, $statsRepo);
        $this->forumService = $forumService;
        $this->userRepo = $userRepo;
        $this->roleRepo = $roleRepo;
    }

    /**
     * Gets the viewer's user and alliance role.
     * Redirects if the user is not in an alliance.
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
        $data['allianceId'] = $viewerData['allianceId'];
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
            // Service returns null if not found/unauthorized
            $this->session->setFlash('error', 'Topic not found or access denied.');
            $this->redirect('/alliance/forum');
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
        
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'title' => 'required|string|min:3|max:255',
            'content' => 'required|string|min:3|max:10000'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/forum/create');
            return;
        }

        // 3. Execute Logic
        $response = $this->forumService->createTopic(
            $viewerData['user']->id, 
            $viewerData['allianceId'], 
            $data['title'], 
            $data['content']
        );

        // 4. Handle Response
        if ($response->isSuccess()) {
            $this->redirect('/alliance/forum/topic/' . $response->data['topic_id']);
        } else {
            $this->session->setFlash('error', $response->message);
            $this->redirect('/alliance/forum/create');
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
        
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'content' => 'required|string|min:3|max:10000'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/forum/topic/' . $topicId);
            return;
        }

        // 3. Execute Logic
        $response = $this->forumService->createPost($viewerData['user']->id, $topicId, $data['content']);
        
        // 4. Handle Response
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
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
        $data = $this->validate($_POST, ['csrf_token' => 'required']);
        
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/forum/topic/' . $topicId);
            return;
        }

        $response = $this->forumService->toggleTopicPin($viewerData['user']->id, $topicId);
        
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
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
        $data = $this->validate($_POST, ['csrf_token' => 'required']);
        
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/forum/topic/' . $topicId);
            return;
        }

        $response = $this->forumService->toggleTopicLock($viewerData['user']->id, $topicId);
        
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/alliance/forum/topic/' . $topicId);
    }
}