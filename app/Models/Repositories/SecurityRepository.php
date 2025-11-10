<?php

namespace App\Models\Repositories;

use App\Models\Entities\UserSecurity;
use PDO;

/**
 * Handles all database operations for the 'user_security' table.
 */
class SecurityRepository
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Finds a user's security data by their user ID.
     *
     * @param int $userId
     * @return UserSecurity|null
     */
    public function findByUserId(int $userId): ?UserSecurity
    {
        $stmt = $this->db->prepare("SELECT * FROM user_security WHERE user_id = ?");
        $stmt->execute([$userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Creates or updates a user's security questions and hashed answers.
     * This uses an "UPSERT" query.
     *
     * @param int $userId
     * @param string $q1
     * @param string $a1_hash
     * @param string $q2
     * @param string $a2_hash
     * @return bool True on success
     */
    public function createOrUpdate(
        int $userId,
        string $q1,
        string $a1_hash,
        string $q2,
        string $a2_hash
    ): bool {
        $sql = "
            INSERT INTO user_security (user_id, question_1, answer_1_hash, question_2, answer_2_hash) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                question_1 = VALUES(question_1),
                answer_1_hash = VALUES(answer_1_hash),
                question_2 = VALUES(question_2),
                answer_2_hash = VALUES(answer_2_hash)
        ";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId, $q1, $a1_hash, $q2, $a2_hash]);
    }

    /**
     * Helper method to convert a database row (array) into a UserSecurity entity.
     *
     * @param array $data
     * @return UserSecurity
     */
    private function hydrate(array $data): UserSecurity
    {
        return new UserSecurity(
            user_id: (int)$data['user_id'],
            question_1: $data['question_1'] ?? null,
            answer_1_hash: $data['answer_1_hash'] ?? null,
            question_2: $data['question_2'] ?? null,
            answer_2_hash: $data['answer_2_hash'] ?? null
        );
    }
}