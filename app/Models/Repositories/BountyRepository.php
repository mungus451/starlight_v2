<?php

namespace App\Models\Repositories;

use PDO;

/**
* Handles database operations for the 'bounties' table.
*/
class BountyRepository
{
private PDO $db;

public function __construct(PDO $db)
{
$this->db = $db;
}

/**
* Creates a new bounty on a target user.
*
* @param int $targetId
* @param int $placerId
* @param float $amount
* @return int The ID of the new bounty
*/
public function create(int $targetId, int $placerId, float $amount): int
{
$sql = "
INSERT INTO bounties (target_id, placer_id, amount, status, created_at, updated_at)
VALUES (?, ?, ?, 'active', NOW(), NOW())
";
$stmt = $this->db->prepare($sql);
$stmt->execute([$targetId, $placerId, $amount]);

return (int)$this->db->lastInsertId();
}

/**
* Gets all active bounties with target info, sorted by highest amount.
*
* @param int $limit
* @return array
*/
public function getActiveBounties(int $limit = 20): array
{
$sql = "
SELECT b.*, u.character_name as target_name
FROM bounties b
JOIN users u ON b.target_id = u.id
WHERE b.status = 'active'
ORDER BY b.amount DESC
LIMIT ?
";
$stmt = $this->db->prepare($sql);
$stmt->bindParam(1, $limit, PDO::PARAM_INT);
$stmt->execute();

return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
* Finds the highest active bounty on a specific target (if any).
* Used by AttackService to check if a win results in a payout.
*
* @param int $targetId
* @return array|null
*/
public function findActiveByTargetId(int $targetId): ?array
{
$sql = "
SELECT * FROM bounties
WHERE target_id = ? AND status = 'active'
ORDER BY amount DESC
LIMIT 1
";
$stmt = $this->db->prepare($sql);
$stmt->execute([$targetId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

return $result ?: null;
}

/**
* Claims a bounty for a winner.
* Updates status to 'claimed' and records the winner ID.
*
* @param int $bountyId
* @param int $winnerId
* @return bool True on success
*/
public function claimBounty(int $bountyId, int $winnerId): bool
{
$sql = "
UPDATE bounties
SET status = 'claimed', claimed_by_id = ?, updated_at = NOW()
WHERE id = ? AND status = 'active'
";
$stmt = $this->db->prepare($sql);
$stmt->execute([$winnerId, $bountyId]);

return $stmt->rowCount() > 0;
}
}