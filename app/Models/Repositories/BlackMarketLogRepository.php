<?php

namespace App\Models\Repositories;

use PDO;

/**
* Handles database operations for the 'black_market_logs' table.
*/
class BlackMarketLogRepository
{
public function __construct(
private PDO $db
) {
}

/**
* Records a Black Market transaction.
*
* @param int $userId
* @param string $actionType ('conversion', 'purchase', 'bounty', 'shadow_contract')
* @param string $costCurrency ('credits', 'crystals')
* @param float $costAmount
* @param string|null $itemKey Optional item identifier (e.g. 'void_container')
* @param array|null $details Optional metadata (will be JSON encoded)
* @return int The ID of the log entry
*/
public function log(
int $userId,
string $actionType,
string $costCurrency,
float $costAmount,
?string $itemKey = null,
?array $details = null
): int {
$sql = "
INSERT INTO black_market_logs
(user_id, action_type, cost_currency, cost_amount, item_key, details_json, created_at)
VALUES
(:user_id, :action_type, :cost_currency, :cost_amount, :item_key, :details_json, NOW())
";

$stmt = $this->db->prepare($sql);

$stmt->execute([
':user_id' => $userId,
':action_type' => $actionType,
':cost_currency' => $costCurrency,
':cost_amount' => $costAmount,
':item_key' => $itemKey,
':details_json' => $details ? json_encode($details) : null
]);

return (int)$this->db->lastInsertId();
}
}