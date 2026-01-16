<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\ViewContextService;
use App\Models\Services\AllianceOperationService;

class AllianceOperationsController extends BaseController
{
    private AllianceOperationService $opService;

    public function __construct(
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService,
        AllianceOperationService $opService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->opService = $opService;
    }

    /**
     * Handles donating TURNS/RESOURCES to Alliance Energy (Legacy/AE)
     * For now, this just calls the generic turn donation logic via the service if we move it there,
     * OR we keep the legacy "Donate AE" separate from "Contribute to Op".
     * 
     * Since the proposal merges them conceptually, we can keep "Donate Turns" as a specific action
     * or route it through the service. For now, I'll keep the AE donation separate but we could
     * refactor it into the service too.
     */
    public function donateTurns(): void
    {
        // For brevity, assuming we keep the direct turn->AE logic here or move it to service.
        // Given the instructions focused on "Deployment Drill" contribution, I will focus on that.
        // But to be clean, let's leave this method mostly as is but cleaner? 
        // Or actually, let's implement the AE donation in the service too.
        // I will stick to the previous implementation for donateTurns for now to minimize risk,
        // as the user specifically asked about the "Deployment Drill" contribution logic.
        
        // RE-IMPLEMENTING PREVIOUS LOGIC (Simplified)
        // Ideally this should also be in the service.
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'amount' => 'required|int|min:1'
        ]);

        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid token.']);
            return;
        }
        
        // ... (Existing logic for AE donation is fine, focusing on Op Contribution)
        // Actually, let's leave it. The user reported issues with "Donate 100 turns".
        // I fixed that in the previous step. 
        // I will just re-instate the working code here for donateTurns.
        
        // Wait, I can't access repos directly if I removed them from constructor.
        // I should Move AE donation to the Service.
        
        $userId = $this->session->get('user_id');
        $amount = (int)$data['amount'];
        
        // Calling Service method (I'll add this method to the service next)
        $response = $this->opService->donateEnergy($userId, $amount);
        
        $this->jsonResponse([
            'success' => $response->isSuccess(),
            'message' => $response->message
        ]);
    }

    /**
     * Handles contributing to the Active Operation (Drills, Drives, etc).
     */
    public function contributeToOp(): void
    {
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'op_id' => 'required|int',
            'amount' => 'required|int|min:1'
        ]);

        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid token.']);
            return;
        }

        $userId = $this->session->get('user_id');
        $opId = (int)$data['op_id'];
        $amount = (int)$data['amount'];

        $response = $this->opService->processContribution($userId, $opId, $amount);

        $this->jsonResponse([
            'success' => $response->isSuccess(),
            'message' => $response->message
        ]);
    }
}