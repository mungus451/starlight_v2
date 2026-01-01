<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\Services\BlackMarketService;
use App\Models\Services\CurrencyConverterService;
use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\ViewContextService;

class BlackMarketController extends BaseController
{
private BlackMarketService $bmService;
private CurrencyConverterService $converterService;

public function __construct(
BlackMarketService $bmService,
CurrencyConverterService $converterService,
Session $session,
CSRFService $csrfService,
Validator $validator,
ViewContextService $viewContextService
) {
parent::__construct($session, $csrfService, $validator, $viewContextService);
$this->bmService = $bmService;
$this->converterService = $converterService;
}

// --- Tab 1: The Exchange ---
public function showExchange(): void
{
$userId = $this->session->get('user_id');
$data = $this->converterService->getConverterPageData($userId);
$data['active_tab'] = 'exchange';
$this->render('black_market/exchange.php', $data + ['title' => 'Black Market - Exchange']);
}

    // --- Tab 2: The Undermarket (Actions) ---
    public function showActions(): void
    {
        $userId = $this->session->get('user_id');
        
        // Use Service to get data, keeping Controller thin
        $data = $this->bmService->getUndermarketPageData($userId);
        
        // Load costs config to pass to view
        $bmConfig = require __DIR__ . '/../../config/black_market.php';

        $this->render('black_market/actions.php', [
            'title' => 'Black Market - Actions',
            'active_tab' => 'actions',
            'bounties' => $data['bounties'],
            'targets' => $data['targets'],
            'isHighRiskActive' => $data['isHighRiskActive'] ?? false,
            'isSafehouseCooldown' => $data['isSafehouseCooldown'] ?? false,
            'costs' => $bmConfig['costs'], // Pass costs array
            'layoutMode' => 'full'
        ]);
    }    
    // --- Purchase Handler (Stat, Energy, Citizens, Lootbox) ---
    public function handlePurchase(array $vars): void
    {
        $action = $vars['action'] ?? '';

        $this->validate($_POST, ['csrf_token' => 'required']);
        if (!$this->csrfService->validateToken($_POST['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid token.');
            $this->redirect('/black-market/actions');
            return;
        }

        $userId = $this->session->get('user_id');
        $response = null;

        switch ($action) {
            case 'respec':
                $response = $this->bmService->purchaseStatRespec($userId);
                break;
            case 'refill':
                $response = $this->bmService->purchaseTurnRefill($userId);
                break;
            case 'citizens':
                $response = $this->bmService->purchaseCitizens($userId);
                break;
            case 'lootbox':
                $response = $this->bmService->openVoidContainer($userId);
                break;
            case 'radar_jamming':
                $response = $this->bmService->purchaseRadarJamming($userId);
                break;
            case 'safehouse':
                $response = $this->bmService->purchaseSafehouse($userId);
                break;
            case 'high_risk':
                $response = $this->bmService->purchaseHighRiskBuff($userId);
                break;
            case 'terminate_high_risk':
                $response = $this->bmService->terminateHighRiskProtocol($userId);
                break;
            default:$this->session->setFlash('error', 'Invalid action.');
$this->redirect('/black-market/actions');
return;
}

if ($response->isSuccess()) {
// Check for specific outcome type (e.g., 'negative' from a bad lootbox roll)
$flashType = ($response->data['outcome_type'] ?? 'success') === 'negative' ? 'error' : 'success';
$this->session->setFlash($flashType, $response->message);
} else {
$this->session->setFlash('error', $response->message);
}
$this->redirect('/black-market/actions');
}

// --- Launder Credits ---
public function handleLaunder(): void
{
$data = $this->validate($_POST, [
    'csrf_token' => 'required',
    'amount' => 'required|int|min:1'
]);

if (!$this->csrfService->validateToken($data['csrf_token'])) {
    $this->session->setFlash('error', 'Invalid token.');
    $this->redirect('/black-market/actions');
    return;
}

$userId = $this->session->get('user_id');
$response = $this->bmService->launderCredits($userId, $data['amount']);

if ($response->isSuccess()) {
    $this->session->setFlash('success', $response->message);
} else {
    $this->session->setFlash('error', $response->message);
}
$this->redirect('/black-market/actions');
}

// --- Withdraw Untraceable Chips ---
public function handleWithdrawChips(): void
{
    $data = $this->validate($_POST, [
        'csrf_token' => 'required',
        'amount' => 'required|int|min:1'
    ]);

    if (!$this->csrfService->validateToken($data['csrf_token'])) {
        $this->session->setFlash('error', 'Invalid token.');
        $this->redirect('/dashboard'); // Redirect to dashboard after withdrawal
        return;
    }

    $userId = $this->session->get('user_id');
    $response = $this->bmService->withdrawChips($userId, $data['amount']);

    if ($response->isSuccess()) {
        $this->session->setFlash('success', $response->message);
    } else {
        $this->session->setFlash('error', $response->message);
    }
    $this->redirect('/dashboard'); // Redirect to dashboard after withdrawal
}

// --- Place Bounty ---
public function handlePlaceBounty(): void
{
$data = $this->validate($_POST, [
'csrf_token' => 'required',
'target_name' => 'required|string',
'amount' => 'required|float|min:10'
]);

if (!$this->csrfService->validateToken($data['csrf_token'])) {
$this->session->setFlash('error', 'Invalid token.');
$this->redirect('/black-market/actions');
return;
}

$userId = $this->session->get('user_id');
$response = $this->bmService->placeBounty($userId, $data['target_name'], $data['amount']);

if ($response->isSuccess()) {
$this->session->setFlash('success', $response->message);
} else {
$this->session->setFlash('error', $response->message);
}
$this->redirect('/black-market/actions');
}

// --- Shadow Contract ---
public function handleShadowContract(): void
{
$data = $this->validate($_POST, [
'csrf_token' => 'required',
'target_name' => 'required|string'
]);

if (!$this->csrfService->validateToken($data['csrf_token'])) {
$this->session->setFlash('error', 'Invalid token.');
$this->redirect('/black-market/actions');
return;
}

$userId = $this->session->get('user_id');
$response = $this->bmService->purchaseShadowContract($userId, $data['target_name']);

if ($response->isSuccess()) {
$this->session->setFlash('success', $response->message);
} else {
$this->session->setFlash('error', $response->message);
}
$this->redirect('/black-market/actions');
}
}