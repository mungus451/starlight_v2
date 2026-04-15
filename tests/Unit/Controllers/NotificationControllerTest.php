<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Controllers\NotificationController;
use App\Core\Config;
use App\Core\CSRFService;
use App\Core\Exceptions\TerminateException;
use App\Core\ServiceResponse;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Services\NotificationService;
use App\Models\Services\ViewContextService;
use Mockery;
use Tests\Unit\TestCase;

class NotificationControllerTest extends TestCase
{
    private NotificationController $controller;
    private NotificationService|Mockery\MockInterface $notificationService;
    private Session|Mockery\MockInterface $session;
    private CSRFService|Mockery\MockInterface $csrfService;
    private Validator|Mockery\MockInterface $validator;
    private ViewContextService|Mockery\MockInterface $viewContextService;
    private Config|Mockery\MockInterface $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notificationService = Mockery::mock(NotificationService::class);
        $this->session = Mockery::mock(Session::class);
        $this->csrfService = Mockery::mock(CSRFService::class);
        $this->validator = Mockery::mock(Validator::class);
        $this->viewContextService = Mockery::mock(ViewContextService::class);
        $this->config = Mockery::mock(Config::class);

        $this->controller = new NotificationController(
            $this->notificationService,
            $this->session,
            $this->csrfService,
            $this->validator,
            $this->viewContextService,
            $this->config
        );

        $_GET = [];
        $_POST = [];
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);

        parent::tearDown();
    }

    public function testApiListReturns401WhenUnauthenticated(): void
    {
        $this->session->shouldReceive('get')
            ->with('user_id', 0)
            ->once()
            ->andReturn(0);

        $this->notificationService->shouldNotReceive('getPaginatedNotifications');

        [$statusCode, $payload] = $this->invokeAndCapture(fn() => $this->controller->apiList());

        $this->assertSame(401, $statusCode);
        $this->assertSame(['error' => 'Not authenticated'], $payload);
    }

    public function testApiListClampsPaginationBounds(): void
    {
        $_GET['page'] = '0';
        $_GET['per_page'] = '500';

        $this->session->shouldReceive('get')
            ->with('user_id', 0)
            ->once()
            ->andReturn(7);

        $notification = (object) [
            'id' => 10,
            'type' => 'system',
            'title' => 'Signal',
            'message' => 'Ping',
            'link' => null,
            'is_read' => false,
            'created_at' => '2026-03-08 14:00:00',
        ];

        $this->notificationService->shouldReceive('getPaginatedNotifications')
            ->with(7, 1, 50)
            ->once()
            ->andReturn([
                'notifications' => [$notification],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 50,
                    'total_items' => 1,
                    'total_pages' => 1,
                    'has_previous' => false,
                    'has_next' => false,
                ],
            ]);

        [$statusCode, $payload] = $this->invokeAndCapture(fn() => $this->controller->apiList());

        $this->assertSame(200, $statusCode);
        $this->assertCount(1, $payload['notifications']);
        $this->assertSame(50, $payload['pagination']['per_page']);
        $this->assertSame(10, $payload['notifications'][0]['id']);
        $this->assertSame('Signal', $payload['notifications'][0]['title']);
    }

    public function testApiMarkReadReturns400OnServiceFailure(): void
    {
        $this->session->shouldReceive('get')
            ->with('user_id', 0)
            ->once()
            ->andReturn(3);

        $this->notificationService->shouldReceive('markAsRead')
            ->with(12, 3)
            ->once()
            ->andReturn(ServiceResponse::error('Unable to mark notification.'));

        [$statusCode, $payload] = $this->invokeAndCapture(fn() => $this->controller->apiMarkRead(['id' => '12']));

        $this->assertSame(400, $statusCode);
        $this->assertSame(['error' => 'Unable to mark notification.'], $payload);
    }

    public function testApiMarkReadSuccess(): void
    {
        $this->session->shouldReceive('get')
            ->with('user_id', 0)
            ->once()
            ->andReturn(3);

        $this->notificationService->shouldReceive('markAsRead')
            ->with(12, 3)
            ->once()
            ->andReturn(ServiceResponse::success('ok'));

        [$statusCode, $payload] = $this->invokeAndCapture(fn() => $this->controller->apiMarkRead(['id' => '12']));

        $this->assertSame(200, $statusCode);
        $this->assertSame(['success' => true], $payload);
    }

    public function testApiMarkAllReadSuccess(): void
    {
        $this->session->shouldReceive('get')
            ->with('user_id', 0)
            ->once()
            ->andReturn(9);

        $this->notificationService->shouldReceive('markAllRead')
            ->with(9)
            ->once()
            ->andReturn(ServiceResponse::success('ok'));

        [$statusCode, $payload] = $this->invokeAndCapture(fn() => $this->controller->apiMarkAllRead());

        $this->assertSame(200, $statusCode);
        $this->assertSame(['success' => true], $payload);
    }

    public function testApiUpdatePreferencesSuccessWithBooleanConversion(): void
    {
        $_POST = [
            'attack_enabled' => 'yes',
            'spy_enabled' => '0',
            'alliance_enabled' => 'on',
            'system_enabled' => 'false',
            'push_notifications_enabled' => ['unexpected-array'],
        ];

        $this->session->shouldReceive('get')
            ->with('user_id', 0)
            ->once()
            ->andReturn(42);

        $this->notificationService->shouldReceive('updatePreferences')
            ->with(42, true, false, true, false, false)
            ->once()
            ->andReturn(ServiceResponse::success('updated'));

        [$statusCode, $payload] = $this->invokeAndCapture(fn() => $this->controller->apiUpdatePreferences());

        $this->assertSame(200, $statusCode);
        $this->assertSame(['success' => true], $payload);
    }

    /**
     * @param callable():void $action
     * @return array{0:int,1:array<string,mixed>}
     */
    private function invokeAndCapture(callable $action): array
    {
        ob_start();

        try {
            $action();
            $this->fail('Expected TerminateException was not thrown.');
        } catch (TerminateException) {
            $statusCode = http_response_code();
            $raw = ob_get_clean();
            $decoded = json_decode($raw ?: '{}', true);

            $this->assertIsArray($decoded);

            return [$statusCode, $decoded];
        }
    }
}
