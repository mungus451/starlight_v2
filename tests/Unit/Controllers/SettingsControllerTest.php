<?php

namespace Tests\Unit\Controllers;

use Tests\Unit\TestCase;
use App\Controllers\SettingsController;
use App\Models\Services\SettingsService;
use App\Models\Services\ViewContextService;
use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Core\ServiceResponse;
use Mockery;

class SettingsControllerTest extends TestCase
{
    private SettingsController $controller;
    private SettingsService|Mockery\MockInterface $mockSettingsService;
    private Session|Mockery\MockInterface $mockSession;
    private CSRFService|Mockery\MockInterface $mockCsrfService;
    private Validator|Mockery\MockInterface $mockValidator;
    private ViewContextService|Mockery\MockInterface $mockViewContextService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockSettingsService = Mockery::mock(SettingsService::class);
        $this->mockSession = Mockery::mock(Session::class);
        $this->mockCsrfService = Mockery::mock(CSRFService::class);
        $this->mockValidator = Mockery::mock(Validator::class);
        $this->mockViewContextService = Mockery::mock(ViewContextService::class);

        $this->controller = new SettingsController(
            $this->mockSettingsService,
            $this->mockSession,
            $this->mockCsrfService,
            $this->mockValidator,
            $this->mockViewContextService
        );
    }

    public function testHandleNotificationsWithValidInput(): void
    {
        $userId = 1;
        $csrfToken = 'valid-token';
        
        // Mock POST data
        $_POST = [
            'csrf_token' => $csrfToken,
            'attack_enabled' => '1',
            'spy_enabled' => '1',
            'alliance_enabled' => '0',
            'system_enabled' => '1',
            'push_notifications_enabled' => '1'
        ];

        // Mock validator to return a Validator instance
        $mockValidatorInstance = Mockery::mock(Validator::class);
        $mockValidatorInstance->shouldReceive('fails')->andReturn(false);
        $mockValidatorInstance->shouldReceive('validated')->andReturn($_POST);
        $this->mockValidator->shouldReceive('make')
            ->with($_POST, Mockery::any())
            ->andReturn($mockValidatorInstance);

        // Mock CSRF validation
        $this->mockCsrfService->shouldReceive('validateToken')
            ->with($csrfToken)
            ->andReturn(true);

        // Mock session
        $this->mockSession->shouldReceive('get')
            ->with('user_id')
            ->andReturn($userId);

        // Mock service call
        $successResponse = ServiceResponse::success('Preferences updated successfully');
        $this->mockSettingsService->shouldReceive('updateNotificationPreferences')
            ->once()
            ->with($userId, true, true, false, true, true)
            ->andReturn($successResponse);

        // Mock flash message and redirect
        $this->mockSession->shouldReceive('setFlash')
            ->once()
            ->with('success', 'Preferences updated successfully');

        // We can't easily test redirect without more infrastructure,
        // but we can verify the method runs without exceptions
        try {
            $this->controller->handleNotifications();
            $this->assertTrue(true); // Method executed without exception
        } catch (\Exception $e) {
            // Redirect will throw exception in test context, which is expected
            if (strpos($e->getMessage(), 'redirect') === false) {
                throw $e;
            }
        }
    }

    public function testHandleNotificationsWithInvalidCsrf(): void
    {
        $csrfToken = 'invalid-token';
        
        $_POST = [
            'csrf_token' => $csrfToken,
            'attack_enabled' => '1'
        ];

        // Mock validator to return a Validator instance
        $mockValidatorInstance = Mockery::mock(Validator::class);
        $mockValidatorInstance->shouldReceive('fails')->andReturn(false);
        $mockValidatorInstance->shouldReceive('validated')->andReturn($_POST);
        $this->mockValidator->shouldReceive('make')
            ->with($_POST, Mockery::any())
            ->andReturn($mockValidatorInstance);

        // Mock CSRF validation failure
        $this->mockCsrfService->shouldReceive('validateToken')
            ->with($csrfToken)
            ->andReturn(false);

        // Mock flash message
        $this->mockSession->shouldReceive('setFlash')
            ->once()
            ->with('error', 'Invalid security token.');

        // Service should NOT be called
        $this->mockSettingsService->shouldNotReceive('updateNotificationPreferences');

        try {
            $this->controller->handleNotifications();
        } catch (\Exception $e) {
            // Expected redirect exception
        }
    }

    public function testHandleNotificationsConvertsCheckboxesToBooleans(): void
    {
        $userId = 1;
        $csrfToken = 'valid-token';
        
        // Test with only some checkboxes checked
        $_POST = [
            'csrf_token' => $csrfToken,
            'spy_enabled' => '1',
            // Other checkboxes not present (unchecked)
        ];

        // Mock validator to return a Validator instance
        $mockValidatorInstance = Mockery::mock(Validator::class);
        $mockValidatorInstance->shouldReceive('fails')->andReturn(false);
        $mockValidatorInstance->shouldReceive('validated')->andReturn($_POST);
        $this->mockValidator->shouldReceive('make')
            ->with($_POST, Mockery::any())
            ->andReturn($mockValidatorInstance);

        $this->mockCsrfService->shouldReceive('validateToken')->andReturn(true);
        $this->mockSession->shouldReceive('get')->with('user_id')->andReturn($userId);

        // Verify booleans are correctly converted: only spy_enabled should be true
        $this->mockSettingsService->shouldReceive('updateNotificationPreferences')
            ->once()
            ->with($userId, false, true, false, false, false)
            ->andReturn(ServiceResponse::success('Updated'));

        $this->mockSession->shouldReceive('setFlash');

        try {
            $this->controller->handleNotifications();
        } catch (\Exception $e) {
            // Expected redirect
        }
    }

    public function testHandleNotificationsWithServiceFailure(): void
    {
        $userId = 1;
        $csrfToken = 'valid-token';
        
        $_POST = [
            'csrf_token' => $csrfToken,
            'attack_enabled' => '1'
        ];

        // Mock validator to return a Validator instance
        $mockValidatorInstance = Mockery::mock(Validator::class);
        $mockValidatorInstance->shouldReceive('fails')->andReturn(false);
        $mockValidatorInstance->shouldReceive('validated')->andReturn($_POST);
        $this->mockValidator->shouldReceive('make')
            ->with($_POST, Mockery::any())
            ->andReturn($mockValidatorInstance);

        $this->mockCsrfService->shouldReceive('validateToken')->andReturn(true);
        $this->mockSession->shouldReceive('get')->with('user_id')->andReturn($userId);

        // Mock service failure
        $errorResponse = ServiceResponse::error('Failed to update preferences');
        $this->mockSettingsService->shouldReceive('updateNotificationPreferences')
            ->andReturn($errorResponse);

        // Verify error flash message is set
        $this->mockSession->shouldReceive('setFlash')
            ->once()
            ->with('error', 'Failed to update preferences');

        try {
            $this->controller->handleNotifications();
        } catch (\Exception $e) {
            // Expected redirect
        }
    }
}
