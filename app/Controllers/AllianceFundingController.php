<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\AllianceManagementService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles alliance finances (donations and loans).
 * * Refactored to consume ServiceResponse objects.
 */
class AllianceFundingController extends BaseController
{
    private AllianceManagementService $mgmtService;

    /**
     * DI Constructor.
     * 
     * @param AllianceManagementService $mgmtService
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        AllianceManagementService $mgmtService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $validator, $levelCalculator, $statsRepo);
        $this->mgmtService = $mgmtService;
    }

    /**
     * Handles a member's request to DONATE to their alliance bank.
     */
    public function handleDonation(): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'amount' => 'required|int|min:1'
        ]);

        $allianceId = $this->session->get('alliance_id');

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }
        
        $donatorUserId = $this->session->get('user_id');
        
        // 3. Execute Service
        $response = $this->mgmtService->donateToAlliance($donatorUserId, $data['amount']);
        
        // 4. Handle Response
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/alliance/profile/' . $allianceId);
    }

    /**
     * Handles a user's request for a loan.
     */
    public function handleLoanRequest(): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'amount' => 'required|int|min:1'
        ]);

        $allianceId = $this->session->get('alliance_id');
        
        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }
        
        $userId = $this->session->get('user_id');
        $response = $this->mgmtService->requestLoan($userId, $data['amount']);
        
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/alliance/profile/' . $allianceId);
    }

    /**
     * Handles an admin's approval of a loan.
     */
    public function handleLoanApprove(array $vars): void
    {
        // 1. Validate Input (CSRF only)
        $data = $this->validate($_POST, [
            'csrf_token' => 'required'
        ]);

        $allianceId = $this->session->get('alliance_id');
        
        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }
        
        $adminId = $this->session->get('user_id');
        $loanId = (int)($vars['id'] ?? 0);
        
        $response = $this->mgmtService->approveLoan($adminId, $loanId);
        
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/alliance/profile/' . $allianceId);
    }

    /**
     * Handles an admin's denial of a loan.
     */
    public function handleLoanDeny(array $vars): void
    {
        // 1. Validate Input (CSRF only)
        $data = $this->validate($_POST, [
            'csrf_token' => 'required'
        ]);

        $allianceId = $this->session->get('alliance_id');
        
        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }
        
        $adminId = $this->session->get('user_id');
        $loanId = (int)($vars['id'] ?? 0);
        
        $response = $this->mgmtService->denyLoan($adminId, $loanId);
        
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/alliance/profile/' . $allianceId);
    }

    /**
     * Handles a user's repayment of a loan.
     */
    public function handleLoanRepay(array $vars): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'amount' => 'required|int|min:1'
        ]);

        $allianceId = $this->session->get('alliance_id');
        
        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }
        
        $userId = $this->session->get('user_id');
        $loanId = (int)($vars['id'] ?? 0);
        
        $response = $this->mgmtService->repayLoan($userId, $loanId, $data['amount']);
        
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/alliance/profile/' . $allianceId);
    }
}