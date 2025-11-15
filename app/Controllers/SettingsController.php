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

        // --- THIS IS THE CHANGE ---
        // Tell the layout to render in full-width mode
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
        
        // Get data from form
        $bio = (string)($_POST['bio'] ?? '');
        $phone = (string)($_POST['phone_number'] ?? '');
        
        // Get file upload data, defaulting to "no file" error
        $file = $_FILES['profile_picture'] ?? ['error' => UPLOAD_ERR_NO_FILE];
        
        // Check if the "remove" checkbox was sent
        $removePhoto = isset($_POST['remove_picture']) && $_POST['remove_picture'] === '1';

        // Pass all data, including the file array, to the service
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