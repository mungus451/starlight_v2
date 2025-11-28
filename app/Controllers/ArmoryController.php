<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\ArmoryService;
use App\Models\Services\ViewContextService;
use App\Presenters\ArmoryPresenter;

/**
* Handles all HTTP requests for the Armory.
* * Refactored Phase 3: Full MVC Compliance via Presenter.
* * Refactored Phase 4: Uses standardized BaseController::jsonResponse.
*/
class ArmoryController extends BaseController
{
private ArmoryService $armoryService;
private ArmoryPresenter $presenter;

/**
* DI Constructor.
*
* @param ArmoryService $armoryService
* @param ArmoryPresenter $presenter
* @param Session $session
* @param CSRFService $csrfService
* @param Validator $validator
* @param ViewContextService $viewContextService
*/
public function __construct(
ArmoryService $armoryService,
ArmoryPresenter $presenter,
Session $session,
CSRFService $csrfService,
Validator $validator,
ViewContextService $viewContextService
) {
parent::__construct($session, $csrfService, $validator, $viewContextService);
$this->armoryService = $armoryService;
$this->presenter = $presenter;
}

/**
* Displays the main Armory page with all tabs and items.
*/
public function show(): void
{
$userId = $this->session->get('user_id');

// 1. Get Raw Business Data (Service Layer)
$rawData = $this->armoryService->getArmoryData($userId);

// 2. Transform to View Model (Presentation Layer)
$viewModel = $this->presenter->present($rawData);

$viewModel['title'] = 'Armory';
$viewModel['layoutMode'] = 'full';

// 3. Render
$this->render('armory/show.php', $viewModel);
}

/**
* Handles the "Manufacture / Upgrade" form submission.
*/
public function handleManufacture(): void
{
$isJson = $this->wantsJson();
$rules = [
'csrf_token' => 'required',
'item_key' => 'required|string',
'quantity' => 'required|int|min:1'
];

// 1. Validation
if ($isJson) {
$val = $this->validator->make($_POST, $rules);
if ($val->fails()) {
$this->jsonResponse(['success' => false, 'error' => implode(' ', $val->errors())]);
return;
}
$data = $val->validated();

if (!$this->csrfService->validateToken($data['csrf_token'])) {
$this->jsonResponse(['success' => false, 'error' => 'Invalid security token.']);
return;
}
} else {
$data = $this->validate($_POST, $rules);
if (!$this->csrfService->validateToken($data['csrf_token'])) {
$this->session->setFlash('error', 'Invalid security token.');
$this->redirect('/armory');
return;
}
}

// 2. Execute Logic
$userId = $this->session->get('user_id');
$response = $this->armoryService->manufactureItem($userId, $data['item_key'], $data['quantity']);

// 3. Response Handling
if ($isJson) {
if ($response->isSuccess()) {
// Flash message is set so it appears on next full page load if user refreshes
$this->session->setFlash('success', $response->message);

$this->jsonResponse([
'success' => true,
'message' => $response->message,
'new_credits' => $response->data['new_credits'],
'new_owned' => $response->data['new_owned'],
'item_key' => $response->data['item_key']
]);
} else {
$this->jsonResponse(['success' => false, 'error' => $response->message]);
}
} else {
// Standard HTTP Redirect
if ($response->isSuccess()) {
$this->session->setFlash('success', $response->message);
} else {
$this->session->setFlash('error', $response->message);
}
$this->redirect('/armory');
}
}

/**
* Handles the "Equip" form submission.
*/
public function handleEquip(): void
{
$isJson = $this->wantsJson();
$rules = [
'csrf_token' => 'required',
'unit_key' => 'required|string',
'category_key' => 'required|string',
'item_key' => 'nullable|string'
];

// 1. Validation
if ($isJson) {
$val = $this->validator->make($_POST, $rules);
if ($val->fails()) {
$this->jsonResponse(['success' => false, 'error' => implode(' ', $val->errors())]);
return;
}
$data = $val->validated();

if (!$this->csrfService->validateToken($data['csrf_token'])) {
$this->jsonResponse(['success' => false, 'error' => 'Invalid security token.']);
return;
}
} else {
$data = $this->validate($_POST, $rules);
if (!$this->csrfService->validateToken($data['csrf_token'])) {
$this->session->setFlash('error', 'Invalid security token.');
$this->redirect('/armory');
return;
}
}

// 2. Execute Logic
$userId = $this->session->get('user_id');
$itemKey = $data['item_key'] ?? '';

$response = $this->armoryService->equipItem($userId, $data['unit_key'], $data['category_key'], $itemKey);

// 3. Response Handling
if ($isJson) {
if ($response->isSuccess()) {
$this->session->setFlash('success', $response->message);
$this->jsonResponse([
'success' => true,
'message' => $response->message
]);
} else {
$this->jsonResponse(['success' => false, 'error' => $response->message]);
}
} else {
if ($response->isSuccess()) {
$this->session->setFlash('success', $response->message);
} else {
$this->session->setFlash('error', $response->message);
}
$this->redirect('/armory');
}
}

/**
* Helper to detect JSON requests (AJAX).
*/
private function wantsJson(): bool
{
$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
$requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

return str_contains($accept, 'application/json') || $requestedWith === 'XMLHttpRequest';
}
}