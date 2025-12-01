<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\Services\BlackMarketService;
use App\Models\Services\CurrencyConverterService;
use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\ViewContextService;
use App\Models\Repositories\BountyRepository;
use App\Models\Repositories\UserRepository;

class BlackMarketController extends BaseController
{
private BlackMarketService $bmService;
private CurrencyConverterService $converterService;
private BountyRepository $bountyRepo;
private UserRepository $userRepo;

public function __construct(
BlackMarketService $bmService,
CurrencyConverterService $converterService,
BountyRepository $bountyRepo,
UserRepository $userRepo,
Session $session,
CSRFService $csrfService,
Validator $validator,
ViewContextService $viewContextService
) {
parent::__construct($session, $csrfService, $validator, $viewContextService);
$this->bmService = $bmService;
$this->converterService = $converterService;
$this->bountyRepo = $bountyRepo;
$this->userRepo = $userRepo;
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
$bounties = $this->bountyRepo->getActiveBounties(10);
$targets = $this->userRepo->findAllNonNpcs();

// Load costs config to pass to view
$bmConfig = require __DIR__ . '/../../config/black_market.php';

$this->render('black_market/actions.php', [
'title' => 'Black Market - Actions',
'active_tab' => 'actions',
'bounties' => $bounties,
'targets' => $targets,
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
default:
$this->session->setFlash('error', 'Invalid action.');
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