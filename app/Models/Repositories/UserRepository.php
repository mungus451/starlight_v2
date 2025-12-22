<?php

namespace App\Models\Repositories;

use App\Core\Database;
use App\Models\Entities\User;
use PDO;

/**
* Handles all database operations for the 'users' table.
*/
class UserRepository
{
public function __construct(
private PDO $db
) {
}

/**
* Finds a user by their email address.
*/
public function findByEmail(string $email): ?User
{
$stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

return $data ? $this->hydrate($data) : null;
}

/**
* Finds a user by their character name.
*/
public function findByCharacterName(string $charName): ?User
{
$stmt = $this->db->prepare("SELECT * FROM users WHERE character_name = ?");
$stmt->execute([$charName]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

return $data ? $this->hydrate($data) : null;
}

/**
* Finds a user by their ID.
*/
public function findById(int $id): ?User
{
$stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

return $data ? $this->hydrate($data) : null;
}

/**
* Creates a new user in the database.
*/
public function createUser(string $email, string $charName, string $passwordHash): int
{
$stmt = $this->db->prepare(
"INSERT INTO users (email, character_name, password_hash) VALUES (?, ?, ?)"
);
$stmt->execute([$email, $charName, $passwordHash]);

return (int)$this->db->lastInsertId();
}

/**
* Updates the non-sensitive profile information for a user.
*/
public function updateProfile(int $userId, ?string $bio, ?string $pfpUrl, ?string $phone): bool
{
$bio = empty($bio) ? null : $bio;
$pfpUrl = empty($pfpUrl) ? null : $pfpUrl;
$phone = empty($phone) ? null : $phone;

$stmt = $this->db->prepare(
"UPDATE users SET bio = ?, profile_picture_url = ?, phone_number = ? WHERE id = ?"
);
return $stmt->execute([$bio, $pfpUrl, $phone, $userId]);
}

/**
* Updates a user's email address.
*/
public function updateEmail(int $userId, string $newEmail): bool
{
$stmt = $this->db->prepare("UPDATE users SET email = ? WHERE id = ?");
return $stmt->execute([$newEmail, $userId]);
}

/**
* Updates a user's password hash.
*/
public function updatePassword(int $userId, string $newPasswordHash): bool
{
$stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
return $stmt->execute([$newPasswordHash, $userId]);
}

/**
* Gets a simple array of all user IDs in the game.
*/
public function getAllUserIds(): array
{
$stmt = $this->db->query("SELECT id FROM users ORDER BY id ASC");
return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}

// --- ALLIANCE METHODS ---

/**
* Assigns a user to an alliance with a specific role ID.
*/
public function setAlliance(int $userId, int $allianceId, int $roleId): bool
{
$stmt = $this->db->prepare(
"UPDATE users SET alliance_id = ?, alliance_role_id = ? WHERE id = ?"
);
return $stmt->execute([$allianceId, $roleId, $userId]);
}

/**
* Updates a user's role ID within their alliance.
*/
public function setAllianceRole(int $userId, int $newRoleId): bool
{
$stmt = $this->db->prepare(
"UPDATE users SET alliance_role_id = ? WHERE id = ?"
);
return $stmt->execute([$newRoleId, $userId]);
}

/**
* Removes a user from their alliance (sets fields to NULL).
*/
public function leaveAlliance(int $userId): bool
{
$stmt = $this->db->prepare(
"UPDATE users SET alliance_id = NULL, alliance_role_id = NULL WHERE id = ?"
);
return $stmt->execute([$userId]);
}

/**
* Finds all users who are members of a specific alliance.
*/
public function findAllByAllianceId(int $allianceId): array
{
$sql = "
SELECT u.*, ar.name as alliance_role_name
FROM users u
LEFT JOIN alliance_roles ar ON u.alliance_role_id = ar.id
WHERE u.alliance_id = ?
ORDER BY ar.sort_order ASC, u.character_name ASC
";
$stmt = $this->db->prepare($sql);
$stmt->execute([$allianceId]);

return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- NPC METHODS ---

/**
* Finds all users flagged as NPCs.
*
* @return User[]
*/
public function findNpcs(): array
{
$stmt = $this->db->query("SELECT * FROM users WHERE is_npc = 1");
$npcs = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
$npcs[] = $this->hydrate($row);
}
return $npcs;
}

/**
* Retrieves a simple list of all non-NPC characters.
* Used for dropdowns (e.g. Bounty Board, Shadow Contracts).
*
* @return array Array of ['character_name' => 'Name']
*/
public function findAllNonNpcs(): array
{
$stmt = $this->db->query("SELECT character_name FROM users WHERE is_npc = 0 ORDER BY character_name ASC");
return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    // --- END NPC METHODS ---

    /**
     * Updates a user's race_id (one-time selection).
     * 
     * @param int $userId The ID of the user
     * @param int $raceId The ID of the race to assign
     * @return bool True if update was successful
     */
    public function updateRace(int $userId, int $raceId): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET race_id = ? WHERE id = ?");
        return $stmt->execute([$raceId, $userId]);
    }

/**
* Helper method to convert a database row (array) into a User entity.
*/
private function hydrate(array $data): User
{
return new User(
id: (int)$data['id'],
email: $data['email'],
characterName: $data['character_name'],
bio: $data['bio'] ?? null,
profile_picture_url: $data['profile_picture_url'] ?? null,
phone_number: $data['phone_number'] ?? null,
alliance_id: isset($data['alliance_id']) ? (int)$data['alliance_id'] : null,
alliance_role_id: isset($data['alliance_role_id']) ? (int)$data['alliance_role_id'] : null,
race_id: isset($data['race_id']) ? (int)$data['race_id'] : null,
passwordHash: $data['password_hash'],
createdAt: $data['created_at'],
is_npc: (bool)($data['is_npc'] ?? false)
);
}
}