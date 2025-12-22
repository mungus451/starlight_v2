<?php

/**
 * SPY VERTICAL SLICE - FULL STACK INTEGRATION TEST
 *
 * Verifies the complete lifecycle of the Spy feature:
 * 1. Controller Input/Output (Routing, CSRF, Validation, Redirects).
 * 2. Service Logic (Calculations, Transactions, Effects).
 * 3. Repository Persistence (Snapshots, Data Integrity).
 * 4. Presenter Formatting (Narrative Generation).
 *
 * Usage: docker exec starlight_app php tests/SpyVerticalSliceTest.php
 */

if (php_sapi_name() !== 'cli') {
    die('Access Denied: CLI only.');
}

require __DIR__ . '/../../vendor/autoload.php';

// --- CRITICAL FIX: Start Session for CSRF Token Generation in CLI ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Setup Environment
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();
} catch (\Exception $e) {}

use App\Core\ContainerFactory;
use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Exceptions\RedirectException;
use App\Controllers\SpyController;
use App\Models\Services\SpyService;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\SpyRepository;
use App\Models\Repositories\EffectRepository;
use App\Presenters\SpyReportPresenter;

class SpySliceTester
{
    private $container;
    private $db;
    private $session;
    private $csrf;
    
    // Components to Test
    private SpyController $controller;
    private SpyService $service;
    private SpyRepository $spyRepo;
    private SpyReportPresenter $presenter;
    
    // Repos for Setup
    private $userRepo;
    private $resRepo;
    private $statsRepo;
    private $structRepo;
    private $effectRepo;

    // Test Data
    private int $atkId;
    private int $defId;
    private string $defName;

    public function __construct()
    {
        $this->container = ContainerFactory::createContainer();
        $this->db = $this->container->get(PDO::class);
        $this->session = $this->container->get(Session::class);
        $this->csrf = $this->container->get(CSRFService::class);
        
        // Resolve Target Components
        $this->controller = $this->container->get(SpyController::class);
        $this->service = $this->container->get(SpyService::class);
        $this->spyRepo = $this->container->get(SpyRepository::class);
        $this->presenter = $this->container->get(SpyReportPresenter::class);
        
        // Resolve Setup Repos
        $this->userRepo = $this->container->get(UserRepository::class);
        $this->resRepo = $this->container->get(ResourceRepository::class);
        $this->statsRepo = $this->container->get(StatsRepository::class);
        $this->structRepo = $this->container->get(StructureRepository::class);
        $this->effectRepo = $this->container->get(EffectRepository::class);
    }

    public function run()
    {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "   🕵️  SPY VERTICAL SLICE INTEGRATION TEST\n";
        echo str_repeat("=", 70) . "\n";

        $this->db->beginTransaction();
        echo "🔒 Database Transaction Started (Sandbox Mode)\n\n";

        try {
            $this->setupData();
            
            $this->testServiceLogic();
            $this->testControllerShow();
            $this->testControllerAction(); // The big one: handleSpy()
            $this->testPresenterLogic();
            $this->testEffectIntervention();

            echo "\n" . str_repeat("=", 70) . "\n";
            echo "✅ \033[32mALL TESTS PASSED - VERTICAL SLICE IS HEALTHY\033[0m\n";

        } catch (Throwable $e) {
            echo "\n❌ \033[31mTEST FAILED: " . $e->getMessage() . "\033[0m\n";
            echo $e->getTraceAsString();
        } finally {
            $this->db->rollBack();
            echo "\n🔒 Transaction Rolled Back. System Clean.\n";
        }
    }

    private function setupData()
    {
        echo "[SETUP] Creating Test Users...\n";
        $seed = bin2hex(random_bytes(3));
        
        // Attacker
        $this->atkId = $this->userRepo->createUser("s_atk_{$seed}@test.com", "SpyMaster{$seed}", 'hash');
        $this->initUser($this->atkId);
        $this->resRepo->updateSpyAttacker($this->atkId, 100_000_000, 5000); // 5k Spies
        $this->statsRepo->updateAttackTurns($this->atkId, 50);

        // Defender (No underscores to ensure validation passes alpha checks if present)
        $this->defName = "Target{$seed}";
        $this->defId = $this->userRepo->createUser("s_def_{$seed}@test.com", $this->defName, 'hash');
        $this->initUser($this->defId);
        $this->resRepo->updateSpyDefender($this->defId, 200); // 200 Sentries
        
        // Mock Session User
        $this->session->set('user_id', $this->atkId);
        
        // Mock Referer for Validation Redirects (So we know if it's validation failure)
        $_SERVER['HTTP_REFERER'] = '/spy';
        
        echo "   -> Attacker ID: {$this->atkId}, Defender ID: {$this->defId} ({$this->defName})\n";
    }

    private function initUser(int $uid)
    {
        $this->resRepo->createDefaults($uid);
        $this->statsRepo->createDefaults($uid);
        $this->structRepo->createDefaults($uid);
    }

    // --- 1. SERVICE LAYER TESTS ---
    private function testServiceLogic()
    {
        echo "\n\033[1;33m[TEST 1] Service Layer Logic (SpyService)\033[0m\n";
        
        // A. Data Retrieval
        $data = $this->service->getSpyData($this->atkId, 1);
        if ($data['resources']->spies !== 5000) throw new Exception("getSpyData returned incorrect spy count.");
        echo "   ✅ getSpyData() returns correct ViewModel data.\n";

        // B. Operation Execution (Overkill Scenario)
        $this->resRepo->updateSpyAttacker($this->atkId, 1e8, 5000); // Reset
        $response = $this->service->conductOperation($this->atkId, $this->defName);
        
        if (!$response->isSuccess()) throw new Exception("Service conductOperation failed: " . $response->message);
        echo "   ✅ conductOperation() executed successfully.\n";

        // C. Persistence Check
        $reports = $this->spyRepo->findReportsByAttackerId($this->atkId);
        if (empty($reports)) throw new Exception("No report generated in DB.");
        
        $lastReport = $reports[0];
        if ($lastReport->defender_total_sentries !== 200) {
             throw new Exception("Snapshot failed. Expected 200 sentries, got {$lastReport->defender_total_sentries}");
        }
        echo "   ✅ Database Persistence & Snapshot verified.\n";
    }

    // --- 2. CONTROLLER DISPLAY TESTS ---
    private function testControllerShow()
    {
        echo "\n\033[1;33m[TEST 2] Controller Display (SpyController::show)\033[0m\n";
        
        // Mock Route Vars
        $vars = ['page' => 1];
        
        // Capture Output
        ob_start();
        try {
            $this->controller->show($vars);
            $output = ob_get_clean();
            
            // Basic Content Checks
            if (!str_contains($output, 'Espionage')) throw new Exception("View did not render 'Espionage' title.");
            if (!str_contains($output, '5,000 Spies')) throw new Exception("View did not render Spy count.");
            
            echo "   ✅ show() rendered correctly with user data.\n";
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
    }

    // --- 3. CONTROLLER ACTION TESTS ---
    private function testControllerAction()
    {
        echo "\n\033[1;33m[TEST 3] Controller Actions (SpyController::handleSpy)\033[0m\n";

        // A. CSRF Fail
        $_POST['csrf_token'] = 'invalid_token';
        $_POST['target_name'] = $this->defName;
        
        try {
            $this->controller->handleSpy();
            throw new Exception("Controller allowed invalid CSRF.");
        } catch (RedirectException $e) {
            // Flash message is set on the Session object
            $flash = $this->session->getFlash('error');
            if (!str_contains($flash, 'Invalid security token')) throw new Exception("Wrong CSRF error message. Got: " . $flash);
            echo "   ✅ CSRF Protection verified.\n";
        }

        // B. Successful Spy via Controller
        // Setup Valid CSRF
        $_POST['csrf_token'] = $this->csrf->generateToken();
        $_POST['target_name'] = $this->defName;
        
        // Sanity Check for CSRF Setup
        if (empty($_POST['csrf_token'])) {
             throw new Exception("Test Setup Failure: CSRF Token is empty. Session start failed.");
        }

        try {
            $this->controller->handleSpy();
            // Should redirect to reports
        } catch (RedirectException $e) {
            if ($e->getMessage() !== '/spy/reports') {
                $error = $this->session->getFlash('error');
                $debug = " (Redirected to: " . $e->getMessage() . ")";
                throw new Exception("Controller Logic Failed. Error Flash: '{$error}'" . $debug);
            }
            $flash = $this->session->getFlash('success');
            if (empty($flash)) throw new Exception("No success flash message set.");
            echo "   ✅ handleSpy() processed request and redirected correctly.\n";
        }
    }

    // --- 4. PRESENTER LAYER TESTS ---
    private function testPresenterLogic()
    {
        echo "\n\033[1;33m[TEST 4] Presenter Logic (SpyReportPresenter)\033[0m\n";

        // Get the most recent report
        $reports = $this->service->getSpyReports($this->atkId);
        $reportEntity = $reports[0];
        
        // Present it
        $viewModel = $this->presenter->present($reportEntity, $this->atkId);

        // Verify Narrative Generation
        if (!isset($viewModel['story_html'])) throw new Exception("Presenter missing story_html.");
        
        // Verify Narrative Content
        $story = $viewModel['story_html'];
        // "SpyMaster... deployed 5,000 spies..."
        if (!str_contains($story, 'deployed 5,000 spies')) throw new Exception("Narrative missing deployment numbers.");
        if (!str_contains($story, 'Target')) throw new Exception("Narrative missing defender name.");
        
        // Verify Styling Logic
        if ($reportEntity->operation_result === 'success') {
            if ($viewModel['status_class'] !== 'status-victory') throw new Exception("Wrong CSS class for success.");
            if ($viewModel['result_text'] !== 'OPERATION SUCCESSFUL') throw new Exception("Wrong text for success.");
        }

        echo "   ✅ Presenter correctly generated Narrative and UI classes.\n";
    }

    // --- 5. EFFECT INTERVENTION TESTS ---
    private function testEffectIntervention()
    {
        echo "\n\033[1;33m[TEST 5] Edge Case: Effect Intervention\033[0m\n";

        // Apply Peace Shield to Defender
        // Use +24h to ensure no timezone bugs in test environment
        $this->effectRepo->addEffect($this->defId, 'peace_shield', date('Y-m-d H:i:s', strtotime('+24 hours')), null);

        $_POST['csrf_token'] = $this->csrf->generateToken();
        $_POST['target_name'] = $this->defName;

        try {
            $this->controller->handleSpy();
        } catch (RedirectException $e) {
            $flash = $this->session->getFlash('error');
            if (!str_contains($flash, 'Safehouse protection')) {
                throw new Exception("Controller failed to catch Peace Shield error. Got: " . $flash);
            }
            echo "   ✅ Controller correctly handled Service Error (Peace Shield).\n";
        }
    }
}

// Run the Test Suite
$test = new SpySliceTester();
$test->run();