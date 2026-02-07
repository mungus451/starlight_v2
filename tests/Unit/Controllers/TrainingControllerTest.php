<?php

namespace Tests\Unit\Controllers;

use Tests\Unit\TestCase;
use App\Controllers\TrainingController;
use App\Models\Services\TrainingService;
use App\Models\Services\ViewContextService;
use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Core\ServiceResponse;
use App\Presenters\TrainingPresenter;
use Mockery;

class TrainingControllerTest extends TestCase
{
    private TrainingController $controller;
    private TrainingService|Mockery\MockInterface $mockTrainingService;
    private Session|Mockery\MockInterface $mockSession;
    private CSRFService|Mockery\MockInterface $mockCsrfService;
    private Validator|Mockery\MockInterface $mockValidator;
    private ViewContextService|Mockery\MockInterface $mockViewContextService;
    private TrainingPresenter|Mockery\MockInterface $mockTrainingPresenter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockTrainingService = Mockery::mock(TrainingService::class);
        $this->mockSession = Mockery::mock(Session::class);
        $this->mockCsrfService = Mockery::mock(CSRFService::class);
        $this->mockValidator = Mockery::mock(Validator::class);
        $this->mockViewContextService = Mockery::mock(ViewContextService::class);
        $this->mockTrainingPresenter = Mockery::mock(TrainingPresenter::class);

        $this->controller = new TrainingController(
            $this->mockTrainingService,
            $this->mockSession,
            $this->mockCsrfService,
            $this->mockValidator,
            $this->mockViewContextService,
            $this->mockTrainingPresenter
        );
    }

    public function testTrainActionWithSufficientResources(): void
    {
        $userId = 1;
        $csrfToken = 'valid-token';
        
        $_POST = [
            'csrf_token' => $csrfToken,
            'units' => ['soldier' => '10']
        ];

        $mockValidatorInstance = Mockery::mock(Validator::class);
        $mockValidatorInstance->shouldReceive('fails')->andReturn(false);
        $mockValidatorInstance->shouldReceive('validated')->andReturn($_POST);
        $this->mockValidator->shouldReceive('make')
            ->with($_POST, Mockery::any())
            ->andReturn($mockValidatorInstance);

        $this->mockCsrfService->shouldReceive('validateToken')
            ->with($csrfToken)
            ->andReturn(true);

        $this->mockSession->shouldReceive('get')
            ->with('user_id')
            ->andReturn($userId);

        $successResponse = ServiceResponse::success('Units trained successfully');
        $this->mockTrainingService->shouldReceive('trainUnits')
            ->once()
            ->with($userId, 'soldier', 10)
            ->andReturn($successResponse);

        $this->mockSession->shouldReceive('setFlash')
            ->once()
            ->with('success', 'Units have been queued for training.');

        $this->expectException(\App\Core\Exceptions\RedirectException::class);
        $this->expectExceptionMessage('/training');

        $this->controller->handleTrain();
    }

    public function testMaxButtonFunctionalityWithLimitedResources()
    {
        // Mock user stats
        $userStats = [
            'untrained_citizens' => 5,
            'credits' => 1000,
        ];

        // Mock unit cost
        $unitCost = 250;

        // Calculate expected max affordable units
        $maxAffordableByCredit = floor($userStats['credits'] / $unitCost);
        $expectedMaxUnits = min($maxAffordableByCredit, $userStats['untrained_citizens']);

        $this->assertEquals(4, $expectedMaxUnits);
    }
}