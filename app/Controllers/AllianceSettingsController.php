<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Models\Services\AllianceManagementService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles alliance settings and profile configuration.
 */
class AllianceSettingsController extends BaseController
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
     * Handles updating the alliance's public profile (desc, image, recruitment).
     */
    public function handleUpdateProfile(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        $adminId = $this->session->get('user_id');
        $allianceId = $this->session->get('alliance_id');

        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/profile/' . $allianceId);
            return;
        }

        $description = (string)($_POST['description'] ?? '');
        $pfpUrl = (string)($_POST['profile_picture_url'] ?? '');
        $isJoinable = (bool)($_POST['is_joinable'] ?? false);

        $this->mgmtService->updateProfile($adminId, $allianceId, $description, $pfpUrl, $isJoinable);
        
        $this->redirect('/alliance/profile/' . $allianceId);
    }
}