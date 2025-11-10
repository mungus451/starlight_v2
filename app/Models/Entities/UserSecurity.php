<?php

namespace App\Models\Entities;

/**
 * Represents a single row from the 'user_security' table.
 */
class UserSecurity
{
    /**
     * @param int $user_id
     * @param string|null $question_1
     * @param string|null $answer_1_hash (Hashed answer)
     * @param string|null $question_2
     * @param string|null $answer_2_hash (Hashed answer)
     */
    public function __construct(
        public readonly int $user_id,
        public readonly ?string $question_1,
        public readonly ?string $answer_1_hash,
        public readonly ?string $question_2,
        public readonly ?string $answer_2_hash
    ) {
    }
}