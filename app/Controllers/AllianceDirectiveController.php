<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\Permissions;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\ViewContextService;
use App\Models\Services\AllianceService;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRepository; // --- NEW ---

/**
 * Handles Alliance Command Directive actions.
 */
class AllianceDirectiveController extends BaseController
{
    private AllianceService $allianceService;
    private AllianceRoleRepository $roleRepo;
    private UserRepository $userRepo;
    private AllianceRepository $allianceRepo; // --- NEW ---

    public function __construct(
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService,
        AllianceService $allianceService,
        AllianceRoleRepository $roleRepo,
        UserRepository $userRepo,
        AllianceRepository $allianceRepo // --- NEW ---
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->allianceService = $allianceService;
        $this->roleRepo = $roleRepo;
        $this->userRepo = $userRepo;
        $this->allianceRepo = $allianceRepo;
    }

    /**
     * AJAX: Get directive options for the modal.
     */
    public function getOptions(): void
    {
        if (!$this->isLeaderOrOfficer()) {
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
        if (!$this->isLeaderOrOfficer()) {
            $this->session->setFlash('error', 'Unauthorized access.');
            $this->redirect('/dashboard');
            return;
        }

        $allianceId = $this->session->get('alliance_id');
        $alliance = $this->allianceRepo->findById($allianceId);
        $options = $this->allianceService->getDirectiveOptions($allianceId);

        $this->render('alliance/directive.php', [
            'title' => 'Alliance Command Center',
            'options' => $options,
            'activeType' => $alliance->directive_type ?? null
        ]);
    }

    /**
     * AJAX: Set a new directive.
     */
    public function setDirective(): void
    {
        // 1. Authorization
        if (!$this->isLeaderOrOfficer()) {
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

    private function isLeaderOrOfficer(): bool
    {
        $userId = $this->session->get('user_id');
        $allianceId = $this->session->get('alliance_id');
        
        if (!$userId || !$allianceId) return false;

        // 1. Check if Alliance Leader
        $alliance = $this->allianceRepo->findById($allianceId);
        if ($alliance && $alliance->leader_id === $userId) {
            return true;
        }

        // 2. Check Role Permission
        $user = $this->userRepo->findById($userId);
        if (!$user) return false;
        
        $role = $this->roleRepo->findById($user->alliance_role_id);
        return $role && $role->hasPermission(Permissions::CAN_MANAGE_DIPLOMACY); // Or specific 'can_set_directive' later
    }
}
