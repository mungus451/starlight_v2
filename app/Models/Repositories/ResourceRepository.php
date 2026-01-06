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
SELECT user_id, credits, banked_credits, gemstones, naquadah_crystals, untrained_citizens, workers, soldiers, guards, spies, sentries, untraceable_chips, research_data, dark_matter, protoform
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

public function updateChips(int $userId, int $newAmount): bool
{
$stmt = $this->db->prepare("UPDATE user_resources SET untraceable_chips = ? WHERE user_id = ?");
return $stmt->execute([$newAmount, $userId]);
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

public function updateSpyDefender(int $userId, int $newSentries): bool
{
$stmt = $this->db->prepare("UPDATE user_resources SET sentries = ? WHERE user_id = ?");
return $stmt->execute([$newSentries, $userId]);
}

public function updateBattleAttacker(int $userId, int $newCredits, int $newSoldiers): bool
{
$stmt = $this->db->prepare("UPDATE user_resources SET credits = ?, soldiers = ? WHERE user_id = ?");
return $stmt->execute([$newCredits, $newSoldiers, $userId]);
}

public function updateBattleDefender(int $userId, int $newCredits, int $newGuards): bool
{
$stmt = $this->db->prepare("UPDATE user_resources SET credits = ?, guards = ? WHERE user_id = ?");
return $stmt->execute([$newCredits, $newGuards, $userId]);
}

public function applyTurnIncome(int $userId, int $creditsGained, int $interestGained, int $citizensGained, int $researchDataGained, float $darkMatterGained, float $naquadahGained, float $protoformGained): bool
{
$sql = "UPDATE user_resources SET credits = credits + ?, banked_credits = banked_credits + ?, untrained_citizens = untrained_citizens + ?, research_data = research_data + ?, dark_matter = dark_matter + ?, naquadah_crystals = naquadah_crystals + ?, protoform = protoform + ? WHERE user_id = ?";
$stmt = $this->db->prepare($sql);
return $stmt->execute([$creditsGained, $interestGained, $citizensGained, $researchDataGained, $darkMatterGained, $naquadahGained, $protoformGained, $userId]);
}

    public function updateResources(int $userId, ?float $creditsChange = null, ?float $naquadahCrystalsChange = null, ?float $darkMatterChange = null, ?float $protoformChange = null, ?int $researchDataChange = null): bool
    {
        $updates = [];
        $params = [];
        if ($creditsChange !== null) { $updates[] = "credits = credits + :credits_change"; $params[':credits_change'] = $creditsChange; }
        if ($naquadahCrystalsChange !== null) { $updates[] = "naquadah_crystals = naquadah_crystals + :naquadah_crystals_change"; $params[':naquadah_crystals_change'] = $naquadahCrystalsChange; }
        if ($darkMatterChange !== null) { $updates[] = "dark_matter = dark_matter + :dark_matter_change"; $params[':dark_matter_change'] = $darkMatterChange; }
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
naquadah_crystals: (float)$data['naquadah_crystals'],
untrained_citizens: (int)$data['untrained_citizens'],
workers: (int)$data['workers'],
soldiers: (int)$data['soldiers'],
guards: (int)$data['guards'],
spies: (int)$data['spies'],
sentries: (int)$data['sentries'],
            untraceable_chips: (int)($data['untraceable_chips'] ?? 0),
            research_data: (int)($data['research_data'] ?? 0),
            dark_matter: (float)($data['dark_matter'] ?? 0.0),
            protoform: (float)($data['protoform'] ?? 0.0)
        );
    }
}