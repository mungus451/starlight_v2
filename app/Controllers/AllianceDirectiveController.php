<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\ViewContextService;
use App\Models\Services\AllianceService;

/**
 * Handles Alliance Command Directive actions.
 */
class AllianceDirectiveController extends BaseController
{
    private AllianceService $allianceService;

    public function __construct(
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService,
        AllianceService $allianceService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->allianceService = $allianceService;
    }

    /**
     * AJAX: Get directive options for the modal.
     */
    public function getOptions(): void
    {
        if (!$this->checkPermission()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 403);
            return;
        }

        $allianceId = $this->session->get('alliance_id');
        $options = $this->allianceService->getDirectiveOptions($allianceId);

        $this->jsonResponse(['options' => $options]);
    }

    /**
     * Show the Directive Management Page.
     */
    public function showPage(): void
    {
        if (!$this->checkPermission()) {
            $this->session->setFlash('error', 'Unauthorized access.');
            $this->redirect('/dashboard');
            return;
        }

        $allianceId = $this->session->get('alliance_id');
        $activeType = $this->allianceService->getAllianceDirectiveType($allianceId);
        $options = $this->allianceService->getDirectiveOptions($allianceId);

        $this->render('alliance/directive.php', [
            'title' => 'Alliance Command Center',
            'options' => $options,
            'activeType' => $activeType
        ]);
    }

    /**
     * AJAX: Set a new directive.
     */
    public function setDirective(): void
    {
        // 1. Authorization
        if (!$this->checkPermission()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 403);
            return;
        }

        // 2. Validation
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($input['type'])) {
            $this->jsonResponse(['error' => 'Invalid directive type'], 400);
            return;
        }

        // 3. Execution
        $allianceId = $this->session->get('alliance_id');
        $success = $this->allianceService->setDirective($allianceId, $input['type']);

        if ($success) {
            $this->session->setFlash('success', 'New directive issued successfully.');
            $this->jsonResponse(['message' => 'Directive set']);
        } else {
            $this->jsonResponse(['error' => 'Failed to set directive'], 500);
        }
    }

    private function checkPermission(): bool
    {
        $userId = $this->session->get('user_id');
        $allianceId = $this->session->get('alliance_id');
        
        if (!$userId || !$allianceId) return false;

        return $this->allianceService->canManageDirectives($userId, $allianceId);
    }
}