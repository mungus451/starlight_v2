<?php

namespace App\Controllers;

use App\Models\Services\SettingsService;

/**
 * Handles all HTTP requests for the Settings page.
 */
class SettingsController extends BaseController
{
    private SettingsService $settingsService;

    public function __construct()
    {
        parent::__construct();
        $this->settingsService = new SettingsService();
    }

    /**
     * Displays the main settings page.
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        $data = $this->settingsService->getSettingsData($userId);

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
        $pfpUrl = (string)($_POST['profile_picture_url'] ?? '');
        $phone = (string)($_POST['phone_number'] ?? '');

        $this->settingsService->updateProfile($userId, $bio, $pfpUrl, $phone);
        
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

        // --- THIS IS THE FIX ---
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

        // --- THIS IS THE FIX ---
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

        // --- THIS IS THE FIX ---
        $this->settingsService->updateSecurityQuestions($userId, $q1, $a1, $q2, $a2, $password);
        
        $this->redirect('/settings');
    }
}