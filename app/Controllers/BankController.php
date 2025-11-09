<?php

namespace App\Controllers;

use App\Models\Services\BankService;

/**
 * Handles all HTTP requests for the Bank.
 */
class BankController extends BaseController
{
    private BankService $bankService;

    public function __construct()
    {
        parent::__construct();
        $this->bankService = new BankService();
    }

    /**
     * Displays the main bank page.
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        $resources = $this->bankService->getBankData($userId);

        $this->render('bank/show.php', [
            'title' => 'Bank',
            'resources' => $resources
        ]);
    }

    /**
     * Handles the deposit form submission.
     */
    public function handleDeposit(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/bank');
            return;
        }

        $userId = $this->session->get('user_id');
        $amount = (int)($_POST['amount'] ?? 0);
        
        // The service handles all logic and flash messages
        $this->bankService->deposit($userId, $amount);
        
        $this->redirect('/bank');
    }

    /**
     * Handles the withdraw form submission.
     */
    public function handleWithdraw(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/bank');
            return;
        }

        $userId = $this->session->get('user_id');
        $amount = (int)($_POST['amount'] ?? 0);
        
        $this->bankService->withdraw($userId, $amount);
        
        $this->redirect('/bank');
    }

    /**
     * Handles the transfer form submission.
     */
    public function handleTransfer(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/bank');
            return;
        }

        $userId = $this->session->get('user_id');
        $amount = (int)($_POST['amount'] ?? 0);
        $recipientName = (string)($_POST['recipient_name'] ?? '');
        
        $this->bankService->transfer($userId, $recipientName, $amount);
        
        $this->redirect('/bank');
    }
}