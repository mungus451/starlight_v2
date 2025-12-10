<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\AllianceForumService;
use App\Models\Services\ViewContextService;
use App\Presenters\AllianceForumPresenter;

/**
 * Handles all HTTP requests for the Alliance Forum.
 * * Refactored Phase 1.1: Removed direct Repository dependencies.
 * * Fixed: Updated parent constructor call to use ViewContextService.
 */
class AllianceForumController extends BaseController
{
    private AllianceForumService $forumService;
    private AllianceForumPresenter $forumPresenter;

    public function __construct(
        AllianceForumService $forumService,
        AllianceForumPresenter $forumPresenter,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->forumService = $forumService;
        $this->forumPresenter = $forumPresenter;
    }

    /**
     * Displays the main forum index (list of topics).
     */
    public function showForum(array $vars): void
    {
        $userId = $this->session->get('user_id');
        $page = (int)($vars['page'] ?? 1);
        
        // Service handles User/Alliance/Role lookups internally
        $response = $this->forumService->getForumIndexData($userId, $page);

        if (!$response->isSuccess()) {
            $this->session->setFlash('error', $response->message);
            $this->redirect('/alliance/list');
            return;
        }

        $data = $this->forumPresenter->presentIndex($response->data);
        $data['layoutMode'] = 'full';

        $this->render('alliance/forum_index.php', $data + ['title' => 'Alliance Forum']);
    }

    /**
     * Displays a single topic and its posts.
     */
    public function showTopic(array $vars): void
    {
        $userId = $this->session->get('user_id');
        $topicId = (int)($vars['id'] ?? 0);
        
        $response = $this->forumService->getTopicDetails($userId, $topicId);

        if (!$response->isSuccess()) {
            $this->session->setFlash('error', $response->message);
            $this->redirect('/alliance/forum');
            return;
        }

        $data = $this->forumPresenter->presentTopic($response->data);
        $data['layoutMode'] = 'full';

        $this->render('alliance/forum_topic.php', $data + ['title' => $data['topic']->title]);
    }

    /**
     * Displays the form to create a new topic.
     */
    public function showCreateTopic(): void
    {
        $userId = $this->session->get('user_id');
        // We need the alliance ID for the Back button in the view
        $allianceId = $this->forumService->getUserAllianceId($userId);

        if (!$allianceId) {
            $this->session->setFlash('error', 'You must be in an alliance.');
            $this->redirect('/alliance/list');
            return;
        }

        $this->render('alliance/forum_create.php', [
            'title' => 'Create New Topic',
            'layoutMode' => 'full',
            'allianceId' => $allianceId
        ]);
    }

    /**
     * Handles the "Create New Topic" form submission.
     */
    public function handleCreateTopic(): void
    {
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'title' => 'required|string|min:3|max:255',
            'content' => 'required|string|min:3|max:10000'
        ]);

        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/forum/create');
            return;
        }

        $userId = $this->session->get('user_id');
        
        $response = $this->forumService->createTopic(
            $userId, 
            $data['title'], 
            $data['content']
        );

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
        $topicId = (int)($vars['id'] ?? 0);
        
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'content' => 'required|string|min:3|max:10000'
        ]);

        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/forum/topic/' . $topicId);
            return;
        }

        $userId = $this->session->get('user_id');
        $response = $this->forumService->createPost($userId, $topicId, $data['content']);
        
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
        $this->validate($_POST, ['csrf_token' => 'required']);
        
        if (!$this->csrfService->validateToken($_POST['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/forum');
            return;
        }

        $userId = $this->session->get('user_id');
        $topicId = (int)($vars['id'] ?? 0);

        $response = $this->forumService->toggleTopicPin($userId, $topicId);
        
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
        $this->validate($_POST, ['csrf_token' => 'required']);
        
        if (!$this->csrfService->validateToken($_POST['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/forum');
            return;
        }

        $userId = $this->session->get('user_id');
        $topicId = (int)($vars['id'] ?? 0);

        $response = $this->forumService->toggleTopicLock($userId, $topicId);
        
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/alliance/forum/topic/' . $topicId);
    }
}