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

     * Searches for users by character name (partial match).

     * Used for Autocomplete.

     * 

     * @return array Array of ['id', 'character_name', 'profile_picture_url']

     */

        public function searchByCharacterName(string $query, int $limit = 10): array

        {

            $sql = "

                SELECT u.id, u.character_name, u.profile_picture_url, s.level

                FROM users u

                LEFT JOIN user_stats s ON u.id = s.user_id

                WHERE u.character_name LIKE ? 

                ORDER BY u.character_name ASC 

                LIMIT ?

            ";

    

            $stmt = $this->db->prepare($sql);

            $term = '%' . $query . '%';

            $stmt->bindParam(1, $term, PDO::PARAM_STR);

            $stmt->bindParam(2, $limit, PDO::PARAM_INT);

            $stmt->execute();

    

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

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

    /**
     * Retrieves a lightweight list of ID and Name for all players.
     * Used for Almanac Dropdowns.
     * 
     * @return array Array of ['id', 'character_name']
     */
    public function getAllPlayersSimple(): array
    {
        $stmt = $this->db->query("SELECT id, character_name FROM users WHERE is_npc = 0 ORDER BY character_name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Sums the Net Worth of all users belonging to a specific alliance.
     * Relies on the `user_stats` table joined via `users.id`.
     */
    public function sumNetWorthByAllianceId(int $allianceId): int
    {
        $sql = "
            SELECT SUM(us.net_worth) 
            FROM users u
            JOIN user_stats us ON u.id = us.user_id
            WHERE u.alliance_id = ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$allianceId]);
        
        return (int)$stmt->fetchColumn();
    }

    public function getMemberIdsByAllianceId(int $allianceId): array
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE alliance_id = ?");
        $stmt->execute([$allianceId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    // --- END NPC METHODS ---
/**
* Helper method to convert a database row (array) into a User entity.
*/
    public function countAllianceMembers(int $allianceId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE alliance_id = ?");
        $stmt->execute([$allianceId]);
        return (int)$stmt->fetchColumn();
    }

    public function getAllActivePlayerIdsAndData(): array
    {
        $sql = "
            SELECT 
                u.id,
                u.character_name,
                u.profile_picture_url,
                u.alliance_id,
                s.level,
                s.war_prestige,
                s.battles_won,
                s.battles_lost,
                a.name as alliance_name,
                a.tag as alliance_tag
            FROM users u
            JOIN user_stats s ON u.id = s.user_id
            LEFT JOIN alliances a ON u.alliance_id = a.id
            WHERE u.is_npc = 0
            ORDER BY u.id ASC
        ";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function hydrate(array $data): User{
return new User(
id: (int)$data['id'],
email: $data['email'],
characterName: $data['character_name'],
bio: $data['bio'] ?? null,
profile_picture_url: $data['profile_picture_url'] ?? null,
phone_number: $data['phone_number'] ?? null,
alliance_id: isset($data['alliance_id']) ? (int)$data['alliance_id'] : null,
alliance_role_id: isset($data['alliance_role_id']) ? (int)$data['alliance_role_id'] : null,
passwordHash: $data['password_hash'],
createdAt: $data['created_at'],
is_npc: (bool)($data['is_npc'] ?? false)
);
}
}