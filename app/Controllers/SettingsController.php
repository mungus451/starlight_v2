<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\SettingsService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles all HTTP requests for the Settings page.
 * * Refactored for Strict Dependency Injection & Centralized Validation.
 * * Decoupled: Consumes ServiceResponse.
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
     * @param Validator $validator
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        SettingsService $settingsService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $validator, $levelCalculator, $statsRepo);
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
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'bio' => 'nullable|string|max:500',
            'phone_number' => 'nullable|string|max:20',
            'remove_picture' => 'nullable' // Checkbox
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/settings');
            return;
        }

        // 3. Execute Logic
        $userId = $this->session->get('user_id');
        
        $file = $_FILES['profile_picture'] ?? ['error' => UPLOAD_ERR_NO_FILE];
        $removePhoto = isset($data['remove_picture']) && $data['remove_picture'] == '1';

        $response = $this->settingsService->updateProfile(
            $userId, 
            $data['bio'] ?? '', 
            $file, 
            $data['phone_number'] ?? '', 
            $removePhoto
        );
        
        // 4. Handle Response
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/settings');
    }

    /**
     * Handles the email update form.
     */
    public function handleEmail(): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'email' => 'required|email',
            'current_password_email' => 'required'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/settings');
            return;
        }

        // 3. Execute Logic
        $userId = $this->session->get('user_id');
        $response = $this->settingsService->updateEmail(
            $userId, 
            $data['email'], 
            $data['current_password_email']
        );
        
        // 4. Handle Response
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/settings');
    }

    /**
     * Handles the password change form.
     */
    public function handlePassword(): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'old_password' => 'required',
            'new_password' => 'required|min:3',
            'confirm_password' => 'required|match:new_password'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/settings');
            return;
        }

        // 3. Execute Logic
        $userId = $this->session->get('user_id');
        $response = $this->settingsService->updatePassword(
            $userId, 
            $data['old_password'], 
            $data['new_password'], 
            $data['confirm_password']
        );
        
        // 4. Handle Response
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/settings');
    }

    /**
     * Handles the security questions update form.
     */
    public function handleSecurity(): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'question_1' => 'required|string|max:255',
            'answer_1' => 'required|string|max:255',
            'question_2' => 'required|string|max:255',
            'answer_2' => 'required|string|max:255',
            'current_password_security' => 'required'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/settings');
            return;
        }

        // 3. Execute Logic
        $userId = $this->session->get('user_id');
        $response = $this->settingsService->updateSecurityQuestions(
            $userId, 
            $data['question_1'], 
            $data['answer_1'], 
            $data['question_2'], 
            $data['answer_2'], 
            $data['current_password_security']
        );
        
        // 4. Handle Response
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/settings');
    }
}