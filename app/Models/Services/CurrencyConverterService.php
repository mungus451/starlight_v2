<?php

namespace App\Models\Services;

use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\HouseFinanceRepository;
use App\Models\Entities\HouseFinance;
use PDO;
use Exception;

class CurrencyConverterService
{
    private const CONVERSION_RATE = 100.0; // 1 Naquadah Crystal = 100 Credits
    private const FEE_PERCENTAGE = 0.10;   // 10% conversion fee

    public function __construct(
        private ResourceRepository $resourceRepository,
        private HouseFinanceRepository $houseFinanceRepository,
        private PDO $db
    ) {
    }

    /**
     * Converts Credits to Naquadah Crystals, applying a fee.
     *
     * @param int $userId The ID of the user performing the conversion.
     * @param float $creditAmount The amount of credits the user wants to convert.
     * @return array An associative array with success status and a message.
     */
    public function convertCreditsToCrystals(int $userId, float $creditAmount): array
    {
        if ($creditAmount <= 0) {
            return ['success' => false, 'message' => 'Conversion amount must be positive.'];
        }

        $userResources = $this->resourceRepository->findByUserId($userId);
        if (!$userResources || $userResources->credits < $creditAmount) {
            return ['success' => false, 'message' => 'Insufficient credits for conversion.'];
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
                creditsAmount: $fee,
                crystalsAmount: 0.0
            );

            if (!$userUpdateSuccess || !$houseUpdateSuccess) {
                throw new Exception("Failed to update one or more balances during transaction.");
            }

            $this->db->commit();
            return ['success' => true, 'message' => sprintf(
                'Successfully converted %s Credits to %s Naquadah Crystals.',
                number_format($creditAmount, 2),
                number_format($crystalsReceived, 4)
            )];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error converting credits to crystals for user {$userId}: " . $e->getMessage());
            return ['success' => false, 'message' => 'A server error occurred during conversion. The transaction was rolled back.'];
        }
    }

    /**
     * Converts Naquadah Crystals to Credits, applying a fee.
     *
     * @param int $userId The ID of the user performing the conversion.
     * @param float $crystalAmount The amount of crystals the user wants to convert.
     * @return array An associative array with success status and a message.
     */
    public function convertCrystalsToCredits(int $userId, float $crystalAmount): array
    {
        if ($crystalAmount <= 0) {
            return ['success' => false, 'message' => 'Conversion amount must be positive.'];
        }

        $userResources = $this->resourceRepository->findByUserId($userId);
        if (!$userResources || $userResources->naquadah_crystals < $crystalAmount) {
            return ['success' => false, 'message' => 'Insufficient Naquadah Crystals for conversion.'];
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
                creditsAmount: 0.0,
                crystalsAmount: $fee
            );

            if (!$userUpdateSuccess || !$houseUpdateSuccess) {
                throw new Exception("Failed to update one or more balances during transaction.");
            }

            $this->db->commit();
            return ['success' => true, 'message' => sprintf(
                'Successfully converted %s Naquadah Crystals to %s Credits.',
                number_format($crystalAmount, 4),
                number_format($creditsReceived, 2)
            )];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error converting crystals to credits for user {$userId}: " . $e->getMessage());
            return ['success' => false, 'message' => 'A server error occurred during conversion. The transaction was rolled back.'];
        }
    }

    /**
     * Retrieves data necessary for displaying the currency converter page.
     *
     * @param int $userId The ID of the current user.
     * @return array An associative array containing user resources and house finances.
     */
    public function getConverterPageData(int $userId): array
    {
        $userResources = $this->resourceRepository->findByUserId($userId);
        $houseFinances = $this->houseFinanceRepository->getHouseFinances();

        if (!$houseFinances) {
            error_log("CRITICAL ERROR: HouseFinances record not found (ID 1). It should have been created by migration.");
            // Create a temporary, non-persistent object to prevent fatal errors in the view
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
