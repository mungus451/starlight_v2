<?php

namespace App\Models\Repositories;

use App\Core\Config;
use App\Models\Entities\EdictDefinition;
use App\Models\Entities\UserEdict;
use PDO;

class EdictRepository
{
    public function __construct(
        private PDO $db,
        private Config $config
    ) {}

    /**
     * Get all edict definitions from config.
     * @return EdictDefinition[]
     */
    public function getAllDefinitions(): array
    {
        $data = $this->config->get('edicts', []);
        $definitions = [];

        foreach ($data as $key => $item) {
            $definitions[$key] = EdictDefinition::fromArray($key, $item);
        }

        return $definitions;
    }

    /**
     * Get a specific definition by key.
     */
    public function getDefinition(string $key): ?EdictDefinition
    {
        $data = $this->config->get('edicts.' . $key);
        if (!$data) {
            return null;
        }
        return EdictDefinition::fromArray($key, $data);
    }

    /**
     * Get all active edicts for a user.
     * @return UserEdict[]
     */
    public function findActiveByUserId(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM user_edicts WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = new UserEdict(
                id: (int)$row['id'],
                user_id: (int)$row['user_id'],
                edict_key: $row['edict_key'],
                created_at: $row['created_at']
            );
        }
        return $results;
    }

    /**
     * Activate an edict for a user.
     */
    public function activate(int $userId, string $edictKey): bool
    {
        $stmt = $this->db->prepare("INSERT INTO user_edicts (user_id, edict_key) VALUES (?, ?)");
        try {
            return $stmt->execute([$userId, $edictKey]);
        } catch (\PDOException $e) {
            // Likely duplicate entry
            return false;
        }
    }

    /**
     * Deactivate an edict for a user.
     */
    public function deactivate(int $userId, string $edictKey): bool
    {
        $stmt = $this->db->prepare("DELETE FROM user_edicts WHERE user_id = ? AND edict_key = ?");
        return $stmt->execute([$userId, $edictKey]);
    }
}
