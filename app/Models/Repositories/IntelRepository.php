<?php

namespace App\Models\Repositories;

use PDO;

class IntelRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function createListing(int $sellerId, int $reportId, float $price): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO intel_listings (seller_id, report_id, price)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$sellerId, $reportId, $price]);
        return (int)$this->db->lastInsertId();
    }

    public function getActiveListings(int $viewerId, int $limit = 50, int $offset = 0): array
    {
        // Don't show listings the viewer created or already bought?
        // Actually, listing active ones is fine.
        $stmt = $this->db->prepare("
            SELECT il.*, 
                   u.character_name as seller_name,
                   sr.attacker_id, sr.defender_id, sr.created_at as report_date,
                   def.character_name as target_name
            FROM intel_listings il
            JOIN users u ON il.seller_id = u.id
            JOIN spy_reports sr ON il.report_id = sr.id
            JOIN users def ON sr.defender_id = def.id
            WHERE il.is_sold = 0
            ORDER BY il.created_at DESC
            LIMIT ? OFFSET ?
        ");
        // Bind params need to be integers for LIMIT/OFFSET in some PDO modes, but usually okay
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getListingById(int $listingId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM intel_listings WHERE id = ?");
        $stmt->execute([$listingId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function markAsSold(int $listingId, int $buyerId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE intel_listings 
            SET is_sold = 1, buyer_id = ? 
            WHERE id = ? AND is_sold = 0
        ");
        return $stmt->execute([$buyerId, $listingId]);
    }
}
