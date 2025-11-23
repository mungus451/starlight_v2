<?php

namespace App\Models\Repositories;

use App\Models\Entities\HouseFinance;
use PDO;

class HouseFinanceRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Retrieves the single HouseFinance record.
     * Assumes there's always a record with ID 1.
     *
     * @return HouseFinance|null The HouseFinance entity or null if not found (should not happen).
     */
    public function getHouseFinances(): ?HouseFinance
    {
        $stmt = $this->db->prepare("SELECT id, credits_taxed, crystals_taxed FROM house_finances WHERE id = 1");
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            // This case should ideally not happen if the migration correctly inserted the initial row.
            return null;
        }

        return new HouseFinance(
            id: (int)$data['id'],
            credits_taxed: (float)$data['credits_taxed'],
            crystals_taxed: (float)$data['crystals_taxed']
        );
    }

    /**
     * Updates the house finances by adding (or subtracting) the specified amounts to the existing totals.
     * This method is designed to be called within a transaction if multiple updates are needed.
     *
     * @param float $creditsAmount The amount of credits to add (can be negative to subtract).
     * @param float $crystalsAmount The amount of crystals to add (can be negative to subtract).
     * @return bool True on success, false on failure.
     */
    public function updateFinances(float $creditsAmount, float $crystalsAmount): bool
    {
        $stmt = $this->db->prepare("
            UPDATE house_finances
            SET
                credits_taxed = credits_taxed + :credits_amount,
                crystals_taxed = crystals_taxed + :crystals_amount
            WHERE id = 1
        ");

        $success = $stmt->execute([
            ':credits_amount' => $creditsAmount,
            ':crystals_amount' => $crystalsAmount
        ]);

        // Ensure the update was successful AND that a row was actually changed.
        return $success && $stmt->rowCount() > 0;
    }
}
