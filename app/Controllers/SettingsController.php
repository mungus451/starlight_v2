<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Models\Services\SettingsService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles all HTTP requests for the Settings page.
 * * Refactored for Strict Dependency Injection.
 */
class SettingsController extends BaseController
{
    private SettingsService $settingsService;

    /**
     * DI Constructor.
     *
     * @param SettingsService $settingsService
     * @param Session $session
     * @param CSRFService $csrfService
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        SettingsService $settingsService,
        Session $session,
        CSRFService $csrfService,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $levelCalculator, $statsRepo);
        $this->settingsService = $settingsService;
    }

    /**
     * Displays the main settings page.
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        $data = $this->settingsService->getSettingsData($userId);

        $data['layoutMode'] = 'full';

        $this->render('settings/show.php', $data + ['title' => 'Settings']);
    }

    /**
     * Handles the public profile update form.
     */
    public function handleProfile(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/settings');
            return;
        }

        $userId = $this->session->get('user_id');
        $bio = (string)($_POST['bio'] ?? '');
        $phone = (string)($_POST['phone_number'] ?? '');
        $file = $_FILES['profile_picture'] ?? ['error' => UPLOAD_ERR_NO_FILE];
        $removePhoto = isset($_POST['remove_picture']) && $_POST['remove_picture'] === '1';

        $this->settingsService->updateProfile($userId, $bio, $file, $phone, $removePhoto);
        
        $this->redirect('/settings');
    }

    /**
     * Handles the email update form.
     */
    public function handleEmail(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/settings');
            return;
        }

        $userId = $this->session->get('user_id');
        $newEmail = (string)($_POST['email'] ?? '');
        $password = (string)($_POST['current_password_email'] ?? '');

        $this->settingsService->updateEmail($userId, $newEmail, $password);
        
        $this->redirect('/settings');
    }

    /**
     * Handles the password change form.
     */
    public function handlePassword(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/settings');
            return;
        }

        $userId = $this->session->get('user_id');
        $oldPass = (string)($_POST['old_password'] ?? '');
        $newPass = (string)($_POST['new_password'] ?? '');
        $confirmPass = (string)($_POST['confirm_password'] ?? '');

        $this->settingsService->updatePassword($userId, $oldPass, $newPass, $confirmPass);
        
        $this->redirect('/settings');
    }

    /**
     * Handles the security questions update form.
     */
    public function handleSecurity(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/settings');
            return;
        }

        $userId = $this->session->get('user_id');
        $q1 = (string)($_POST['question_1'] ?? '');
        $a1 = (string)($_POST['answer_1'] ?? '');
        $q2 = (string)($_POST['question_2'] ?? '');
        $a2 = (string)($_POST['answer_2'] ?? '');
        $password = (string)($_POST['current_password_security'] ?? '');

        $this->settingsService->updateSecurityQuestions($userId, $q1, $a1, $q2, $a2, $password);
        
        $this->redirect('/settings');
    }
}