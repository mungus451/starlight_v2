<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\ViewContextService;
use App\Models\Services\EmbassyService;

class EmbassyController extends BaseController
{
    private EmbassyService $embassyService;

    public function __construct(
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService,
        EmbassyService $embassyService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->embassyService = $embassyService;
    }

    public function index(array $vars = []): void
    {
        $userId = $this->session->get('user_id');
        $data = $this->embassyService->getEmbassyData($userId);
        
        // Always add csrf_token for forms
        $data['csrf_token'] = $this->csrfService->generateToken();

        if ($this->session->get('is_mobile')) {
            // Group edicts by type specifically for the mobile view's nested tabs
            $groupedEdicts = [];
            foreach ($data['available_edicts'] as $edict) {
                $groupedEdicts[$edict->type][] = $edict;
            }
            $data['grouped_edicts'] = $groupedEdicts;
            $this->render('embassy/mobile_index.php', $data);
        } else {
            $this->render('embassy/index.php', $data);
        }
    }

    public function activate(array $vars = []): void
    {
        $userId = $this->session->get('user_id');
        
        if (!$this->csrfService->validateToken($_POST['csrf_token'] ?? '')) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/embassy');
            return;
        }

        $edictKey = $_POST['edict_key'] ?? '';
        $result = $this->embassyService->activateEdict($userId, $edictKey);

        if ($result->success) {
            $this->session->setFlash('success', $result->message);
        } else {
            $this->session->setFlash('error', $result->message);
        }

        $this->redirect('/embassy');
    }

    public function revoke(array $vars = []): void
    {
        $userId = $this->session->get('user_id');

        if (!$this->csrfService->validateToken($_POST['csrf_token'] ?? '')) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/embassy');
            return;
        }

        $edictKey = $_POST['edict_key'] ?? '';
        $result = $this->embassyService->revokeEdict($userId, $edictKey);

        if ($result->success) {
            $this->session->setFlash('success', $result->message);
        } else {
            $this->session->setFlash('error', $result->message);
        }

        $this->redirect('/embassy');
    }
}