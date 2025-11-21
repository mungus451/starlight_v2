<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Models\Services\BankService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles all HTTP requests for the Bank.
 * * Refactored for Strict Dependency Injection.
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
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        BankService $bankService,
        Session $session,
        CSRFService $csrfService,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $levelCalculator, $statsRepo);
        $this->bankService = $bankService;
    }

    /**
     * Displays the main bank page.
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        
        $data = $this->bankService->getBankData($userId);

        $this->render('bank/show.php', $data + [
            'title' => 'Bank',
            'layoutMode' => 'full'
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