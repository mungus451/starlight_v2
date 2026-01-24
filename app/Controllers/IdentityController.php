<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Repositories\UserRepository;
use App\Models\Services\ViewContextService;

class IdentityController extends BaseController
{
    private UserRepository $userRepo;

    public function __construct(
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService,
        UserRepository $userRepo
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->userRepo = $userRepo;
    }

    public function showSelection(): void
    {
        $userId = $this->session->get('user_id');
        $user = $this->userRepo->findById($userId);

        if ($user->race && $user->class) {
            $this->redirect('/dashboard');
            return;
        }

        $this->render('identity/selection.php', [
            'title' => 'Choose Your Identity',
            'layoutMode' => 'full'
        ]);
    }

    public function handleSelection(): void
    {
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'race' => 'required|string|in:Humans,Cyborgs,Sythera,Juggalo',
            'class' => 'required|string|in:Thief,Cleric,Guard,Soldier'
        ]);

        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/choose-identity');
            return;
        }

        $userId = $this->session->get('user_id');
        $this->userRepo->setIdentity($userId, $data['race'], $data['class']);

        $this->session->setFlash('success', "Your identity as a {$data['race']} {$data['class']} has been established.");
        $this->redirect('/dashboard');
    }
}
