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
     * Retrieves a specific HouseFinance record by ID.
     *
     * @param int $id The ID of the house wallet (usually 1).
     * @return HouseFinance|null
     */
    public function getHouseFinances(int $id): ?HouseFinance
    {
        $stmt = $this->db->prepare("SELECT id, credits_taxed, crystals_taxed, dark_matter_taxed FROM house_finances WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return new HouseFinance(
            id: (int)$data['id'],
            credits_taxed: (float)$data['credits_taxed'],
            crystals_taxed: (float)$data['crystals_taxed'],
            dark_matter_taxed: (float)($data['dark_matter_taxed'] ?? 0.0)
        );
    }

    /**
     * Updates a specific house finance record.
     *
     * @param int $id The ID of the house wallet to update.
     * @param float $creditsAmount Amount to add (positive) or subtract (negative).
     * @param float $crystalsAmount Amount to add (positive) or subtract (negative).
     * @param float $darkMatterAmount Amount to add (positive) or subtract (negative).
     * @return bool True on success.
     */
    public function updateFinances(int $id, float $creditsAmount, float $crystalsAmount, float $darkMatterAmount = 0.0): bool
    {
        $stmt = $this->db->prepare("
            UPDATE house_finances
            SET
                credits_taxed = credits_taxed + :credits_amount,
                crystals_taxed = crystals_taxed + :crystals_amount,
                dark_matter_taxed = dark_matter_taxed + :dark_matter_amount
            WHERE id = :id
        ");

        $success = $stmt->execute([
            ':credits_amount' => $creditsAmount,
            ':crystals_amount' => $crystalsAmount,
            ':dark_matter_amount' => $darkMatterAmount,
            ':id' => $id
        ]);

        return $success && $stmt->rowCount() > 0;
    }
}