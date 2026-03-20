<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Core\Config;
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
    private Config $config;
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
        Config $config,
        BankService $bankService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->config = $config;
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
            'layoutMode' => 'full',
            'spa_bank_enabled' => (bool)$this->config->get('spa.bank_enabled', false),
        ]);
    }

    /**
     * API endpoint: return bank data as JSON for the SPA island.
     */
    public function apiData(): void
    {
        $userId = (int)$this->session->get('user_id', 0);
        if ($userId <= 0) {
            $this->jsonResponse(['error' => 'Not authenticated'], 401);
            return;
        }

        $data = $this->bankService->getBankData($userId);
        $resources = $data['resources'];
        $stats = $data['stats'];
        $bankConfig = $data['bankConfig'];

        $this->jsonResponse([
            'resources' => [
                'credits' => (int)$resources->credits,
                'banked_credits' => (int)$resources->banked_credits,
            ],
            'stats' => [
                'deposit_charges' => (int)$stats->deposit_charges,
                'last_deposit_at' => $stats->last_deposit_at !== null ? (string)$stats->last_deposit_at : null,
            ],
            'bankConfig' => [
                'deposit_max_charges' => (int)($bankConfig['deposit_max_charges'] ?? 4),
                'deposit_charge_regen_hours' => (float)($bankConfig['deposit_charge_regen_hours'] ?? 6),
                'deposit_percent_limit' => (float)($bankConfig['deposit_percent_limit'] ?? 0.8),
            ],
        ]);
    }

    public function apiDeposit(): void
    {
        $userId = (int)$this->session->get('user_id', 0);
        if ($userId <= 0) {
            $this->jsonResponse(['error' => 'Not authenticated'], 401);
            return;
        }

        if (!$this->isValidApiCsrf()) {
            $this->jsonResponse(['error' => 'Invalid CSRF token'], 422);
            return;
        }

        $amount = (int)($_POST['amount'] ?? 0);
        $response = $this->bankService->deposit($userId, $amount);

        if (!$response->isSuccess()) {
            $this->jsonResponse(['error' => $response->message], 400);
            return;
        }

        $this->jsonResponse(['success' => true, 'message' => $response->message]);
    }

    public function apiWithdraw(): void
    {
        $userId = (int)$this->session->get('user_id', 0);
        if ($userId <= 0) {
            $this->jsonResponse(['error' => 'Not authenticated'], 401);
            return;
        }

        if (!$this->isValidApiCsrf()) {
            $this->jsonResponse(['error' => 'Invalid CSRF token'], 422);
            return;
        }

        $amount = (int)($_POST['amount'] ?? 0);
        $response = $this->bankService->withdraw($userId, $amount);

        if (!$response->isSuccess()) {
            $this->jsonResponse(['error' => $response->message], 400);
            return;
        }

        $this->jsonResponse(['success' => true, 'message' => $response->message]);
    }

    public function apiTransfer(): void
    {
        $userId = (int)$this->session->get('user_id', 0);
        if ($userId <= 0) {
            $this->jsonResponse(['error' => 'Not authenticated'], 401);
            return;
        }

        if (!$this->isValidApiCsrf()) {
            $this->jsonResponse(['error' => 'Invalid CSRF token'], 422);
            return;
        }

        $amount = (int)($_POST['amount'] ?? 0);
        $recipientName = trim((string)($_POST['recipient_name'] ?? ''));
        $response = $this->bankService->transfer($userId, $recipientName, $amount);

        if (!$response->isSuccess()) {
            $this->jsonResponse(['error' => $response->message], 400);
            return;
        }

        $this->jsonResponse(['success' => true, 'message' => $response->message]);
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

    private function isValidApiCsrf(): bool
    {
        $headerToken = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $postToken = (string)($_POST['csrf_token'] ?? '');
        $token = $headerToken !== '' ? $headerToken : $postToken;

        return $token !== '' && $this->csrfService->validateToken($token);
    }
}
