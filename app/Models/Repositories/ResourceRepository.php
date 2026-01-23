<?php

namespace App\Models\Repositories;

use App\Models\Entities\UserResource;
use PDO;

/**
* Handles all database operations for the 'user_resources' table.
*/
class ResourceRepository
{
public function __construct(
private PDO $db
) {
}

public function findByUserId(int $userId): ?UserResource
{
$stmt = $this->db->prepare("
SELECT user_id, credits, banked_credits, gemstones, untrained_citizens, workers, soldiers, guards, spies, sentries, research_data, protoform
FROM user_resources WHERE user_id = ?
");
$stmt->execute([$userId]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
return $data ? $this->hydrate($data) : null;
}

public function createDefaults(int $userId): void
{
$stmt = $this->db->prepare("INSERT INTO user_resources (user_id) VALUES (?)");
$stmt->execute([$userId]);
}

public function updateBankingCredits(int $userId, int $newCredits, int $newBankedCredits): bool
{
$stmt = $this->db->prepare("UPDATE user_resources SET credits = ?, banked_credits = ? WHERE user_id = ?");
return $stmt->execute([$newCredits, $newBankedCredits, $userId]);
}

public function updateCredits(int $userId, int $newCredits): bool
{
$stmt = $this->db->prepare("UPDATE user_resources SET credits = ? WHERE user_id = ?");
return $stmt->execute([$newCredits, $userId]);
}

public function updateTrainedUnits(int $userId, int $newCredits, int $newUntrained, int $newWorkers, int $newSoldiers, int $newGuards, int $newSpies, int $newSentries): bool {
$stmt = $this->db->prepare("UPDATE user_resources SET credits = ?, untrained_citizens = ?, workers = ?, soldiers = ?, guards = ?, spies = ?, sentries = ? WHERE user_id = ?");
return $stmt->execute([$newCredits, $newUntrained, $newWorkers, $newSoldiers, $newGuards, $newSpies, $newSentries, $userId]);
}

public function incrementUntrainedCitizens(int $userId, int $amount): bool
{
$stmt = $this->db->prepare("
UPDATE user_resources
SET untrained_citizens = untrained_citizens + :amount
WHERE user_id = :user_id
");
return $stmt->execute([
'amount' => $amount,
'user_id' => $userId
]);
}

public function updateSpyAttacker(int $userId, int $newCredits, int $newSpies): bool
{
$stmt = $this->db->prepare("UPDATE user_resources SET credits = ?, spies = ? WHERE user_id = ?");
return $stmt->execute([$newCredits, $newSpies, $userId]);
}

public function updateSpyDefender(int $userId, int $newSentries, int $newWorkers): bool
{
$stmt = $this->db->prepare("UPDATE user_resources SET sentries = ?, workers = ? WHERE user_id = ?");
return $stmt->execute([$newSentries, $newWorkers, $userId]);
}

public function updateBattleAttacker(int $userId, int $newCredits, int $newSoldiers): bool
{
$stmt = $this->db->prepare("UPDATE user_resources SET credits = ?, soldiers = ? WHERE user_id = ?");
return $stmt->execute([$newCredits, $newSoldiers, $userId]);
}

public function updateBattleDefender(int $userId, int $newCredits, int $newGuards, int $newWorkers): bool
{
$stmt = $this->db->prepare("UPDATE user_resources SET credits = ?, guards = ?, workers = ? WHERE user_id = ?");
return $stmt->execute([$newCredits, $newGuards, $newWorkers, $userId]);
}

    public function updateSoldiers(int $userId, int $newSoldiers): bool
    {
        $stmt = $this->db->prepare("UPDATE user_resources SET soldiers = ? WHERE user_id = ?");
        return $stmt->execute([$newSoldiers, $userId]);
    }

    public function applyTurnIncome(int $userId, int $creditsGained, int $interestGained, int $citizensGained, int $researchDataGained, float $protoformGained): bool{
$sql = "UPDATE user_resources SET credits = credits + ?, banked_credits = banked_credits + ?, untrained_citizens = untrained_citizens + ?, research_data = research_data + ?, protoform = protoform + ? WHERE user_id = ?";
$stmt = $this->db->prepare($sql);
return $stmt->execute([$creditsGained, $interestGained, $citizensGained, $researchDataGained, $protoformGained, $userId]);
}

    /**
     * Sums the count of specified unit types for all members of an alliance.
     *
     * @param int $allianceId
     * @param array $unitColumns e.g. ['soldiers', 'guards']
     * @return int
     */
    public function getAggregateUnitsForAlliance(int $allianceId, array $unitColumns): int
    {
        // Sanitize columns to prevent SQL injection (though array input is trusted from service)
        $allowed = ['soldiers', 'guards', 'spies', 'sentries', 'workers'];
        $cols = array_intersect($unitColumns, $allowed);
        if (empty($cols)) return 0;

        $sumExpr = implode(' + ', array_map(fn($c) => "r.$c", $cols));
        
        $sql = "
            SELECT SUM($sumExpr) as total
            FROM user_resources r
            JOIN users u ON r.user_id = u.id
            WHERE u.alliance_id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$allianceId]);
        
        return (int)$stmt->fetchColumn();
    }

    public function updateResources(int $userId, ?float $creditsChange = null, ?float $protoformChange = null, ?int $researchDataChange = null): bool
    {
        $updates = [];
        $params = [];
        if ($creditsChange !== null) { $updates[] = "credits = credits + :credits_change"; $params[':credits_change'] = $creditsChange; }
        if ($protoformChange !== null) { $updates[] = "protoform = protoform + :protoform_change"; $params[':protoform_change'] = $protoformChange; }
        if ($researchDataChange !== null) { $updates[] = "research_data = research_data + :research_data_change"; $params[':research_data_change'] = $researchDataChange; }
        
        if (empty($updates)) return true;
        
        $params[':user_id'] = $userId;
        $sql = "UPDATE user_resources SET " . implode(', ', $updates) . " WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
private function hydrate(array $data): UserResource
{
return new UserResource(
user_id: (int)$data['user_id'],
credits: (int)$data['credits'],
banked_credits: (int)$data['banked_credits'],
gemstones: (int)$data['gemstones'],
untrained_citizens: (int)$data['untrained_citizens'],
workers: (int)$data['workers'],
soldiers: (int)$data['soldiers'],
guards: (int)$data['guards'],
spies: (int)$data['spies'],
sentries: (int)$data['sentries'],
            research_data: (int)($data['research_data'] ?? 0),
            protoform: (float)($data['protoform'] ?? 0.0)
        );
    }
}