<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\BankService;
use App\Models\Services\ViewContextService; // --- NEW DEPENDENCY ---

/**
 * Handles all HTTP requests for the Bank.
 * * Refactored for Strict Dependency Injection & Centralized Validation.
 * * Decoupled: Consumes ServiceResponse for feedback.
 * * Fixed: Updated parent constructor call to use ViewContextService.
 */
class BankController extends BaseController
{
    private BankService $bankService;

    /**
     * DI Constructor.
     *
     * @param BankService $bankService
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param ViewContextService $viewContextService // --- REPLACES LevelCalculator & StatsRepo ---
     */
    public function __construct(
        BankService $bankService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->bankService = $bankService;
    }

    /**
     * Displays the main bank page.
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        
        $data = $this->bankService->getBankData($userId);
        
        $viewData = $data + [
            'title' => 'Bank',
            'layoutMode' => 'full',
            'csrf_token' => $this->csrfService->generateToken()
        ];

        if ($this->session->get('is_mobile')) {
            $this->render('bank/mobile_show.php', $viewData);
        } else {
            $this->render('bank/show.php', $viewData);
        }
    }

    /**
     * Handles the deposit form submission.
     */
    public function handleDeposit(): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'amount' => 'required|int|min:1'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/bank');
            return;
        }

        // 3. Execute Logic
        $userId = $this->session->get('user_id');
        $response = $this->bankService->deposit($userId, $data['amount']);
        
        // 4. Handle Response
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/bank');
    }

    /**
     * Handles the withdraw form submission.
     */
    public function handleWithdraw(): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'amount' => 'required|int|min:1'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/bank');
            return;
        }

        // 3. Execute Logic
        $userId = $this->session->get('user_id');
        $response = $this->bankService->withdraw($userId, $data['amount']);
        
        // 4. Handle Response
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/bank');
    }

    /**
     * Handles the transfer form submission.
     */
    public function handleTransfer(): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'amount' => 'required|int|min:1',
            'recipient_name' => 'required|string|min:3|max:20'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/bank');
            return;
        }

        // 3. Execute Logic
        $userId = $this->session->get('user_id');
        $response = $this->bankService->transfer($userId, $data['recipient_name'], $data['amount']);
        
        // 4. Handle Response
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/bank');
    }
}