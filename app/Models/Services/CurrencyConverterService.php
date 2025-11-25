<?php

namespace App\Models\Services;

use App\Core\ServiceResponse;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\HouseFinanceRepository;
use App\Models\Entities\HouseFinance;
use PDO;
use Exception;

/**
 * Handles the Black Market currency exchange logic.
 * * Refactored Phase 3: Removed repository magic numbers.
 */
class CurrencyConverterService
{
    private const CONVERSION_RATE = 100.0; // 1 Naquadah Crystal = 100 Credits
    private const FEE_PERCENTAGE = 0.10;   // 10% conversion fee
    
    // Business Rule: The main system wallet is always ID 1
    private const HOUSE_WALLET_ID = 1;

    private ResourceRepository $resourceRepository;
    private HouseFinanceRepository $houseFinanceRepository;
    private PDO $db;

    public function __construct(
        ResourceRepository $resourceRepository,
        HouseFinanceRepository $houseFinanceRepository,
        PDO $db
    ) {
        $this->resourceRepository = $resourceRepository;
        $this->houseFinanceRepository = $houseFinanceRepository;
        $this->db = $db;
    }

    /**
     * Converts Credits to Naquadah Crystals, applying a fee.
     *
     * @param int $userId
     * @param float $creditAmount
     * @return ServiceResponse
     */
    public function convertCreditsToCrystals(int $userId, float $creditAmount): ServiceResponse
    {
        if ($creditAmount <= 0) {
            return ServiceResponse::error('Conversion amount must be positive.');
        }

        $userResources = $this->resourceRepository->findByUserId($userId);
        if (!$userResources || $userResources->credits < $creditAmount) {
            return ServiceResponse::error('Insufficient credits for conversion.');
        }

        $this->db->beginTransaction();
        try {
            $fee = $creditAmount * self::FEE_PERCENTAGE;
            $creditsAfterFee = $creditAmount - $fee;
            $crystalsReceived = $creditsAfterFee / self::CONVERSION_RATE;

            $userUpdateSuccess = $this->resourceRepository->updateResources(
                $userId,
                creditsChange: -$creditAmount,
                naquadahCrystalsChange: $crystalsReceived
            );

            $houseUpdateSuccess = $this->houseFinanceRepository->updateFinances(
                self::HOUSE_WALLET_ID, // Explicit ID passed here
                creditsAmount: $fee,
                crystalsAmount: 0.0
            );

            if (!$userUpdateSuccess || !$houseUpdateSuccess) {
                throw new Exception("Failed to update one or more balances during transaction.");
            }

            $this->db->commit();
            
            $msg = sprintf(
                'Successfully converted %s Credits to %s Naquadah Crystals.',
                number_format($creditAmount, 2),
                number_format($crystalsReceived, 4)
            );
            return ServiceResponse::success($msg);

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error converting credits to crystals for user {$userId}: " . $e->getMessage());
            return ServiceResponse::error('A server error occurred during conversion.');
        }
    }

    /**
     * Converts Naquadah Crystals to Credits, applying a fee.
     *
     * @param int $userId
     * @param float $crystalAmount
     * @return ServiceResponse
     */
    public function convertCrystalsToCredits(int $userId, float $crystalAmount): ServiceResponse
    {
        if ($crystalAmount <= 0) {
            return ServiceResponse::error('Conversion amount must be positive.');
        }

        $userResources = $this->resourceRepository->findByUserId($userId);
        if (!$userResources || $userResources->naquadah_crystals < $crystalAmount) {
            return ServiceResponse::error('Insufficient Naquadah Crystals for conversion.');
        }

        $this->db->beginTransaction();
        try {
            $fee = $crystalAmount * self::FEE_PERCENTAGE;
            $crystalsAfterFee = $crystalAmount - $fee;
            $creditsReceived = $crystalsAfterFee * self::CONVERSION_RATE;

            $userUpdateSuccess = $this->resourceRepository->updateResources(
                $userId,
                creditsChange: $creditsReceived,
                naquadahCrystalsChange: -$crystalAmount
            );

            $houseUpdateSuccess = $this->houseFinanceRepository->updateFinances(
                self::HOUSE_WALLET_ID, // Explicit ID passed here
                creditsAmount: 0.0,
                crystalsAmount: $fee
            );

            if (!$userUpdateSuccess || !$houseUpdateSuccess) {
                throw new Exception("Failed to update one or more balances during transaction.");
            }

            $this->db->commit();
            
            $msg = sprintf(
                'Successfully converted %s Naquadah Crystals to %s Credits.',
                number_format($crystalAmount, 4),
                number_format($creditsReceived, 2)
            );
            return ServiceResponse::success($msg);

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error converting crystals to credits for user {$userId}: " . $e->getMessage());
            return ServiceResponse::error('A server error occurred during conversion.');
        }
    }

    /**
     * Retrieves data necessary for displaying the currency converter page.
     */
    public function getConverterPageData(int $userId): array
    {
        $userResources = $this->resourceRepository->findByUserId($userId);
        
        // Pass the specific wallet ID we want to view
        $houseFinances = $this->houseFinanceRepository->getHouseFinances(self::HOUSE_WALLET_ID);

        if (!$houseFinances) {
            // Fallback DTO if missing
            $houseFinances = new HouseFinance(id: 1, credits_taxed: 0.0, crystals_taxed: 0.0);
        }

        return [
            'userResources' => $userResources,
            'houseFinances' => $houseFinances,
            'conversionRate' => self::CONVERSION_RATE,
            'feePercentage' => self::FEE_PERCENTAGE,
        ];
    }
}