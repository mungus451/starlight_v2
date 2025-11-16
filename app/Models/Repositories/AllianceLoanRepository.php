<?php

namespace App\Models\Repositories;

use App\Models\Entities\AllianceLoan;
use PDO;

/**
 * Handles all database operations for the 'alliance_loans' table.
 */
class AllianceLoanRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Finds a loan by its ID.
     *
     * @param int $loanId
     * @return AllianceLoan|null
     */
    public function findById(int $loanId): ?AllianceLoan
    {
        $stmt = $this->db->prepare("SELECT * FROM alliance_loans WHERE id = ?");
        $stmt->execute([$loanId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Finds all loans for a specific alliance, with borrower's name.
     *
     * @param int $allianceId
     * @return AllianceLoan[]
     */
    public function findByAllianceId(int $allianceId): array
    {
        $sql = "
            SELECT 
                al.*,
                u.character_name
            FROM alliance_loans al
            JOIN users u ON al.user_id = u.id
            WHERE al.alliance_id = ?
            ORDER BY al.status = 'pending' DESC, al.status = 'active' DESC, al.created_at DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$allianceId]);
        
        $loans = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $loans[] = $this->hydrate($row);
        }
        return $loans;
    }

    /**
     * Creates a new loan request.
     *
     * @param int $allianceId
     * @param int $userId
     * @param int $amount
     * @return int The ID of the new loan request
     */
    public function createLoanRequest(int $allianceId, int $userId, int $amount): int
    {
        $sql = "
            INSERT INTO alliance_loans 
                (alliance_id, user_id, amount_requested, amount_to_repay, status)
            VALUES
                (?, ?, ?, ?, 'pending')
        ";
        
        $stmt = $this->db->prepare($sql);
        // We set amount_to_repay = amount_requested initially.
        // A real system might add interest, but for now they are the same.
        $stmt->execute([$allianceId, $userId, $amount, $amount]); 
        
        return (int)$this->db->lastInsertId();
    }

    /**
     * Updates a loan's status and amount_to_repay.
     *
     * @param int $loanId
     * @param string $newStatus ('active', 'paid', 'denied')
     * @param int $newAmountToRepay
     * @return bool
     */
    public function updateLoan(int $loanId, string $newStatus, int $newAmountToRepay): bool
    {
        $sql = "
            UPDATE alliance_loans
            SET status = ?, amount_to_repay = ?
            WHERE id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$newStatus, $newAmountToRepay, $loanId]);
    }

    /**
     * Helper method to convert a database row into an AllianceLoan entity.
     *
     * @param array $data
     * @return AllianceLoan
     */
    private function hydrate(array $data): AllianceLoan
    {
        return new AllianceLoan(
            id: (int)$data['id'],
            alliance_id: (int)$data['alliance_id'],
            user_id: (int)$data['user_id'],
            amount_requested: (int)$data['amount_requested'],
            amount_to_repay: (int)$data['amount_to_repay'],
            status: $data['status'],
            created_at: $data['created_at'],
            updated_at: $data['updated_at'],
            character_name: $data['character_name'] ?? null
        );
    }
}