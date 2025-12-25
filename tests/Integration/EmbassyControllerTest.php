<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Controllers\EmbassyController;
use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\ViewContextService;
use App\Models\Services\EmbassyService;
use App\Core\ServiceResponse;

class EmbassyControllerTest extends TestCase
{
    private $embassyService;
    private $session;
    private $csrfService;
    private $validator;
    private $viewContextService;
    private $embassyController;

    protected function setUp(): void
    {
        $this->embassyService = $this->createMock(EmbassyService::class);
        $this->session = $this->createMock(Session::class);
        $this->csrfService = $this->createMock(CSRFService::class);
        $this->validator = $this->createMock(Validator::class);
        $this->viewContextService = $this->createMock(ViewContextService::class);

        // Mock the BaseController's render method
        $this->embassyController = new class(
            $this->session,
            $this->csrfService,
            $this->validator,
            $this->viewContextService,
            $this->embassyService
        ) extends EmbassyController {
            public function render(string $view, array $data = []): void
            {
                // Do nothing
            }
            public function redirect(string $url): void
            {
                // Do nothing
            }
        };
    }

    public function testIndex()
    {
        $userId = 1;
        $embassyData = [
            'embassy_level' => 1,
            'max_slots' => 1,
            'slots_used' => 0,
            'active_edicts' => [],
            'active_keys' => [],
            'available_edicts' => []
        ];

        $this->session->method('get')
            ->willReturnMap([
                ['user_id', null, $userId],
                ['is_mobile', null, false]
            ]);
        $this->embassyService->expects($this->once())->method('getEmbassyData')->with($userId)->willReturn($embassyData);
        $this->csrfService->method('generateToken')->willReturn('test_token');

        $this->embassyController->index();
    }

    public function testActivateEdictSuccess()
    {
        $userId = 1;
        $edictKey = 'test_edict';
        $csrfToken = 'valid_token';

        $_POST['edict_key'] = $edictKey;
        $_POST['csrf_token'] = $csrfToken;

        $this->session->method('get')->with('user_id')->willReturn($userId);
        $this->csrfService->method('validateToken')->with($csrfToken)->willReturn(true);
        $this->embassyService->method('activateEdict')->with($userId, $edictKey)->willReturn(new ServiceResponse(true, 'Edict activated.'));

        $this->session->expects($this->once())->method('setFlash')->with('success', 'Edict activated.');
        
        $this->embassyController->activate();
    }

    public function testActivateEdictFailure()
    {
        $userId = 1;
        $edictKey = 'test_edict';
        $csrfToken = 'valid_token';

        $_POST['edict_key'] = $edictKey;
        $_POST['csrf_token'] = $csrfToken;

        $this->session->method('get')->with('user_id')->willReturn($userId);
        $this->csrfService->method('validateToken')->with($csrfToken)->willReturn(true);
        $this->embassyService->method('activateEdict')->with($userId, $edictKey)->willReturn(new ServiceResponse(false, 'Edict activation failed.'));

        $this->session->expects($this->once())->method('setFlash')->with('error', 'Edict activation failed.');

        $this->embassyController->activate();
    }

    public function testRevokeEdictSuccess()
    {
        $userId = 1;
        $edictKey = 'test_edict';
        $csrfToken = 'valid_token';

        $_POST['edict_key'] = $edictKey;
        $_POST['csrf_token'] = $csrfToken;

        $this->session->method('get')->with('user_id')->willReturn($userId);
        $this->csrfService->method('validateToken')->with($csrfToken)->willReturn(true);
        $this->embassyService->method('revokeEdict')->with($userId, $edictKey)->willReturn(new ServiceResponse(true, 'Edict revoked.'));

        $this->session->expects($this->once())->method('setFlash')->with('success', 'Edict revoked.');

        $this->embassyController->revoke();
    }

    public function testRevokeEdictFailure()
    {
        $userId = 1;
        $edictKey = 'test_edict';
        $csrfToken = 'valid_token';

        $_POST['edict_key'] = $edictKey;
        $_POST['csrf_token'] = $csrfToken;

        $this->session->method('get')->with('user_id')->willReturn($userId);
        $this->csrfService->method('validateToken')->with($csrfToken)->willReturn(true);
        $this->embassyService->method('revokeEdict')->with($userId, $edictKey)->willReturn(new ServiceResponse(false, 'Edict not active.'));

        $this->session->expects($this->once())->method('setFlash')->with('error', 'Edict not active.');

        $this->embassyController->revoke();
    }

    public function testActivateEdictInvalidToken()
    {
        $userId = 1;
        $edictKey = 'test_edict';
        $csrfToken = 'invalid_token';

        $_POST['edict_key'] = $edictKey;
        $_POST['csrf_token'] = $csrfToken;

        $this->session->method('get')->with('user_id')->willReturn($userId);
        $this->csrfService->method('validateToken')->with($csrfToken)->willReturn(false);

        $this->embassyService->expects($this->never())->method('activateEdict');
        $this->session->expects($this->once())->method('setFlash')->with('error', 'Invalid security token.');

        $this->embassyController->activate();
    }

    public function testRevokeEdictInvalidToken()
    {
        $userId = 1;
        $edictKey = 'test_edict';
        $csrfToken = 'invalid_token';

        $_POST['edict_key'] = $edictKey;
        $_POST['csrf_token'] = $csrfToken;

        $this->session->method('get')->with('user_id')->willReturn($userId);
        $this->csrfService->method('validateToken')->with($csrfToken)->willReturn(false);

        $this->embassyService->expects($this->never())->method('revokeEdict');
        $this->session->expects($this->once())->method('setFlash')->with('error', 'Invalid security token.');

        $this->embassyController->revoke();
    }

    public function testIndexMobileView()
    {
        $userId = 1;
        $embassyData = [
            'embassy_level' => 1,
            'max_slots' => 1,
            'slots_used' => 0,
            'active_edicts' => [],
            'active_keys' => [],
            'available_edicts' => [
                (object)['type' => 'military', 'name' => 'Edict 1'],
                (object)['type' => 'economy', 'name' => 'Edict 2']
            ]
        ];

        $this->session->method('get')
            ->willReturnMap([
                ['user_id', null, $userId],
                ['is_mobile', null, true]
            ]);
        $this->embassyService->method('getEmbassyData')->with($userId)->willReturn($embassyData);
        $this->csrfService->method('generateToken')->willReturn('test_token');

        $this->embassyService->expects($this->once())->method('getEmbassyData');

        $this->embassyController->index();
    }

    public function testIndexMobileViewGroupedEdicts()
    {
        $userId = 1;
        $militaryEdict = (object)['type' => 'military', 'name' => 'Edict 1'];
        $economyEdict = (object)['type' => 'economy', 'name' => 'Edict 2'];
        $embassyData = [
            'embassy_level' => 1,
            'max_slots' => 1,
            'slots_used' => 0,
            'active_edicts' => [],
            'active_keys' => [],
            'available_edicts' => [$militaryEdict, $economyEdict]
        ];

        $this->session->method('get')
            ->willReturnMap([
                ['user_id', null, $userId],
                ['is_mobile', null, true]
            ]);
        $this->embassyService->method('getEmbassyData')->with($userId)->willReturn($embassyData);
        $this->csrfService->method('generateToken')->willReturn('test_token');

        $this->embassyController = new class(
            $this->session,
            $this->csrfService,
            $this->validator,
            $this->viewContextService,
            $this->embassyService
        ) extends EmbassyController {
            public $renderedData = null;

            public function render(string $view, array $data = []): void
            {
                $this->renderedData = $data;
            }

            public function redirect(string $url): void
            {
                // Do nothing
            }
        };

        $this->embassyController->index();

        $renderedData = $this->embassyController->renderedData;

        $this->assertArrayHasKey('grouped_edicts', $renderedData);
        $this->assertArrayHasKey('military', $renderedData['grouped_edicts']);
        $this->assertArrayHasKey('economy', $renderedData['grouped_edicts']);
        $this->assertCount(1, $renderedData['grouped_edicts']['military']);
        $this->assertCount(1, $renderedData['grouped_edicts']['economy']);
        $this->assertEquals($militaryEdict, $renderedData['grouped_edicts']['military'][0]);
        $this->assertEquals($economyEdict, $renderedData['grouped_edicts']['economy'][0]);
    }

    public function testIndexMobileViewNoEdicts()
    {
        $userId = 1;
        $embassyData = [
            'embassy_level' => 1,
            'max_slots' => 1,
            'slots_used' => 0,
            'active_edicts' => [],
            'active_keys' => [],
            'available_edicts' => []
        ];

        $this->session->method('get')
            ->willReturnMap([
                ['user_id', null, $userId],
                ['is_mobile', null, true]
            ]);
        $this->embassyService->method('getEmbassyData')->with($userId)->willReturn($embassyData);
        $this->csrfService->method('generateToken')->willReturn('test_token');

        $this->embassyController = new class(
            $this->session,
            $this->csrfService,
            $this->validator,
            $this->viewContextService,
            $this->embassyService
        ) extends EmbassyController {
            public $renderedData = null;

            public function render(string $view, array $data = []): void
            {
                $this->renderedData = $data;
            }

            public function redirect(string $url): void
            {
                // Do nothing
            }
        };

        $this->embassyController->index();

        $renderedData = $this->embassyController->renderedData;

        $this->assertArrayHasKey('grouped_edicts', $renderedData);
        $this->assertEmpty($renderedData['grouped_edicts']);
    }

    public function testActivateEdictSlotsFull()
    {
        $userId = 1;
        $edictKey = 'test_edict';
        $csrfToken = 'valid_token';

        $_POST['edict_key'] = $edictKey;
        $_POST['csrf_token'] = $csrfToken;

        $this->session->method('get')->with('user_id')->willReturn($userId);
        $this->csrfService->method('validateToken')->with($csrfToken)->willReturn(true);
        $this->embassyService->method('activateEdict')
            ->with($userId, $edictKey)
            ->willReturn(new ServiceResponse(false, 'Edict slots full.'));

        $this->session->expects($this->once())->method('setFlash')->with('error', 'Edict slots full.');

        $this->embassyController->activate();
    }

    public function testActivateEdictNoEmbassy()
    {
        $userId = 1;
        $edictKey = 'test_edict';
        $csrfToken = 'valid_token';

        $_POST['edict_key'] = $edictKey;
        $_POST['csrf_token'] = $csrfToken;

        $this->session->method('get')->with('user_id')->willReturn($userId);
        $this->csrfService->method('validateToken')->with($csrfToken)->willReturn(true);
        $this->embassyService->method('activateEdict')
            ->with($userId, $edictKey)
            ->willReturn(new ServiceResponse(false, 'You must build an Embassy first.'));

        $this->session->expects($this->once())->method('setFlash')->with('error', 'You must build an Embassy first.');

        $this->embassyController->activate();
    }

    public function testActivateEdictAlreadyActive()
    {
        $userId = 1;
        $edictKey = 'test_edict';
        $csrfToken = 'valid_token';

        $_POST['edict_key'] = $edictKey;
        $_POST['csrf_token'] = $csrfToken;

        $this->session->method('get')->with('user_id')->willReturn($userId);
        $this->csrfService->method('validateToken')->with($csrfToken)->willReturn(true);
        $this->embassyService->method('activateEdict')
            ->with($userId, $edictKey)
            ->willReturn(new ServiceResponse(false, 'Edict is already active.'));

        $this->session->expects($this->once())->method('setFlash')->with('error', 'Edict is already active.');

        $this->embassyController->activate();
    }

    public function testActivateEdictInvalidKey()
    {
        $userId = 1;
        $edictKey = 'invalid_key';
        $csrfToken = 'valid_token';

        $_POST['edict_key'] = $edictKey;
        $_POST['csrf_token'] = $csrfToken;

        $this->session->method('get')->with('user_id')->willReturn($userId);
        $this->csrfService->method('validateToken')->with($csrfToken)->willReturn(true);
        $this->embassyService->method('activateEdict')
            ->with($userId, $edictKey)
            ->willReturn(new ServiceResponse(false, 'Invalid edict.'));

        $this->session->expects($this->once())->method('setFlash')->with('error', 'Invalid edict.');

        $this->embassyController->activate();
    }
}
