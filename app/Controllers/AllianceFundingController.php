<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Models\Services\AllianceManagementService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles alliance finances (donations and loans).
 */
class AllianceFundingController extends BaseController
{
    private AllianceManagementService $mgmtService;

    public function __construct(
        AllianceManagementService $mgmtService,
        Session $session,
        CSRFService $csrfService,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $levelCalculator, $statsRepo);
        $this->mgmtService = $mgmtService;
    }

    /**
     * Handles a member's request to DONATE to their alliance bank.
     */
    public function handleDonation(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        $donatorUserId = $this->session->get('user_id');
        $allianceId = $this->session->get('alliance_id');

        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }
        
        $amount = (int)($_POST['amount'] ?? 0);

        $this->mgmtService->donateToAlliance($donatorUserId, $amount);
        
        $this->redirect('/alliance/profile/' . $allianceId);
    }

    /**
     * Handles a user's request for a loan.
     */
    public function handleLoanRequest(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        $userId = $this->session->get('user_id');
        $allianceId = $this->session->get('alliance_id');
        
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }
        
        $amount = (int)($_POST['amount'] ?? 0);
        $this->mgmtService->requestLoan($userId, $amount);
        $this->redirect('/alliance/profile/' . $allianceId);
    }

    /**
     * Handles an admin's approval of a loan.
     */
    public function handleLoanApprove(array $vars): void
    {
        $token = $_POST['csrf_token'] ?? '';
        $adminId = $this->session->get('user_id');
        $allianceId = $this->session->get('alliance_id');
        
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }
        
        $loanId = (int)($vars['id'] ?? 0);
        $this->mgmtService->approveLoan($adminId, $loanId);
        $this->redirect('/alliance/profile/' . $allianceId);
    }

    /**
     * Handles an admin's denial of a loan.
     */
    public function handleLoanDeny(array $vars): void
    {
        $token = $_POST['csrf_token'] ?? '';
        $adminId = $this->session->get('user_id');
        $allianceId = $this->session->get('alliance_id');
        
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }
        
        $loanId = (int)($vars['id'] ?? 0);
        $this->mgmtService->denyLoan($adminId, $loanId);
        $this->redirect('/alliance/profile/' . $allianceId);
    }

    /**
     * Handles a user's repayment of a loan.
     */
    public function handleLoanRepay(array $vars): void
    {
        $token = $_POST['csrf_token'] ?? '';
        $userId = $this->session->get('user_id');
        $allianceId = $this->session->get('alliance_id');
        
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }
        
        $loanId = (int)($vars['id'] ?? 0);
        $amount = (int)($_POST['amount'] ?? 0);
        $this->mgmtService->repayLoan($userId, $loanId, $amount);
        $this->redirect('/alliance/profile/' . $allianceId);
    }
}