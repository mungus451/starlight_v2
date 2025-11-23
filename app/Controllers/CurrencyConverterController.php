<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Models\Services\CurrencyConverterService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

class CurrencyConverterController extends BaseController
{
    private CurrencyConverterService $converterService;

    public function __construct(
        CurrencyConverterService $converterService,
        Session $session,
        CSRFService $csrfService,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $levelCalculator, $statsRepo);
        $this->converterService = $converterService;
    }

    /**
     * Displays the main currency converter page.
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        $data = $this->converterService->getConverterPageData($userId);
        
        // Pass the generated CSRF token to the view
        $data['csrf_token'] = $this->csrfService->generateToken();
        
        $this->render('black-market/converter.php', $data + ['title' => 'Naquadah Crystal Exchange']);
    }

    /**
     * Handles the conversion form submission.
     */
    public function handleConversion(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/black-market/converter');
            return;
        }

        $userId = $this->session->get('user_id');
        $direction = (string)($_POST['conversion_direction'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);

        if ($amount <= 0) {
            $this->session->setFlash('error', 'Conversion amount must be a positive number.');
            $this->redirect('/black-market/converter');
            return;
        }

        $result = [];
        if ($direction === 'credits_to_crystals') {
            $result = $this->converterService->convertCreditsToCrystals($userId, $amount);
        } elseif ($direction === 'crystals_to_credits') {
            $result = $this->converterService->convertCrystalsToCredits($userId, $amount);
        } else {
            $this->session->setFlash('error', 'Invalid conversion type specified.');
            $this->redirect('/black-market/converter');
            return;
        }

        if ($result['success']) {
            $this->session->setFlash('success', $result['message']);
        } else {
            $this->session->setFlash('error', $result['message']);
        }

        $this->redirect('/black-market/converter');
    }
}
