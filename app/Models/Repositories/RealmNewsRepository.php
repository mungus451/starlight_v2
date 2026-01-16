<?php

namespace App\Models\Repositories;

use PDO;

class RealmNewsRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Fetches the latest active news item.
     *
     * @return object|null
     */
    public function findLatestActive(): ?object
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM realm_news WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return $result ?: null;
    }
}
