<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\GeneralService;
use App\Models\Services\ViewContextService;
use App\Models\Repositories\GeneralRepository;
use App\Models\Repositories\ResourceRepository;
use App\Core\Config;

class GeneralController extends BaseController
{
    private GeneralService $generalService;
    private GeneralRepository $generalRepo;
    private ResourceRepository $resourceRepo;
    private Config $config;

    public function __construct(
        GeneralService $generalService,
        GeneralRepository $generalRepo,
        ResourceRepository $resourceRepo,
        Config $config,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->generalService = $generalService;
        $this->generalRepo = $generalRepo;
        $this->resourceRepo = $resourceRepo;
        $this->config = $config;
    }

    public function index(): void
    {
        $userId = $this->session->get('user_id');
        $generals = $this->generalRepo->findByUserId($userId);
        $resources = $this->resourceRepo->findByUserId($userId);
        
        $count = count($generals);
        $nextCost = $this->generalService->getRecruitmentCost($count);
        $cap = $this->generalService->getArmyCapacity($userId);
        
        $weapons = $this->config->get('elite_weapons', []);
        
        $viewData = [
            'title' => 'High Command',
            'generals' => $generals,
            'resources' => $resources,
            'next_cost' => $nextCost,
            'army_cap' => $cap,
            'elite_weapons' => $weapons,
            'layoutMode' => 'full'
        ];

        if ($this->session->get('is_mobile')) {
            $this->render('generals/mobile_index.php', $viewData);
        } else {
            $this->render('generals/index.php', $viewData);
        }
    }

    public function recruit(): void
    {
        // ... (No changes needed)
        $rules = ['csrf_token' => 'required', 'name' => 'nullable|string|max:50'];
        $data = $this->validate($_POST, $rules);
        
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid token.');
            $this->redirect('/generals');
            return;
        }
        
        $userId = $this->session->get('user_id');
        $res = $this->generalService->recruitGeneral($userId, $data['name'] ?? '');
        
        if ($res->isSuccess()) {
            $this->session->setFlash('success', $res->message);
        } else {
            $this->session->setFlash('error', $res->message);
        }
        $this->redirect('/generals');
    }

    public function equip(): void
    {
        // ... (No changes needed)
        $rules = [
            'csrf_token' => 'required', 
            'general_id' => 'required|int',
            'weapon_key' => 'required|string'
        ];
        $data = $this->validate($_POST, $rules);
        
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid token.');
            $this->redirect('/generals');
            return;
        }
        
        $userId = $this->session->get('user_id');
        $res = $this->generalService->equipWeapon($userId, (int)$data['general_id'], $data['weapon_key']);
        
        if ($res->isSuccess()) {
            $this->session->setFlash('success', $res->message);
        } else {
            $this->session->setFlash('error', $res->message);
        }
        $this->redirect('/generals');
    }

    public function armory(array $params): void
    {
        $generalId = (int)($params['id'] ?? 0);
        $userId = $this->session->get('user_id');
        
        $general = $this->generalRepo->findById($generalId);
        if (!$general || $general['user_id'] != $userId) {
            $this->session->setFlash('error', 'General not found.');
            $this->redirect('/generals');
            return;
        }
        
        $resources = $this->resourceRepo->findByUserId($userId);
        $weapons = $this->config->get('elite_weapons', []);
        
        $viewData = [
            'title' => 'Elite Armory: ' . $general['name'],
            'general' => $general,
            'resources' => $resources,
            'elite_weapons' => $weapons,
            'layoutMode' => 'full'
        ];

        if ($this->session->get('is_mobile')) {
            $this->render('generals/mobile_armory.php', $viewData);
        } else {
            $this->render('generals/armory.php', $viewData);
        }
    }

    public function decommission(): void
    {
        $rules = ['csrf_token' => 'required', 'general_id' => 'required|int'];
        $data = $this->validate($_POST, $rules);
        
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid token.');
            $this->redirect('/generals');
            return;
        }
        
        $userId = $this->session->get('user_id');
        $res = $this->generalService->decommissionGeneral($userId, (int)$data['general_id']);
        
        if ($res->isSuccess()) {
            $this->session->setFlash('success', $res->message);
        } else {
            $this->session->setFlash('error', $res->message);
        }
        $this->redirect('/generals');
    }
}