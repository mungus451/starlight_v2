<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\CurrencyConverterService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles the Black Market Currency Exchange.
 * * Refactored to consume ServiceResponse objects.
 */
class CurrencyConverterController extends BaseController
{
    private CurrencyConverterService $converterService;

    /**
     * DI Constructor.
     * 
     * @param CurrencyConverterService $converterService
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        CurrencyConverterService $converterService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $validator, $levelCalculator, $statsRepo);
        $this->converterService = $converterService;
    }

    /**
     * Displays the main currency converter page.
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        $data = $this->converterService->getConverterPageData($userId);
        
        $this->render('black-market/converter.php', $data + ['title' => 'Naquadah Crystal Exchange']);
    }

    /**
     * Handles the conversion form submission.
     */
    public function handleConversion(): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'conversion_direction' => 'required|string|in:credits_to_crystals,crystals_to_credits',
            'amount' => 'required|float|min:0.01'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/black-market/converter');
            return;
        }

        $userId = $this->session->get('user_id');
        
        // 3. Execute Logic
        if ($data['conversion_direction'] === 'credits_to_crystals') {
            $response = $this->converterService->convertCreditsToCrystals($userId, $data['amount']);
        } else {
            $response = $this->converterService->convertCrystalsToCredits($userId, $data['amount']);
        }

        // 4. Handle Response
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }

        $this->redirect('/black-market/converter');
    }
}