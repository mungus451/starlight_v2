<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUserSecurity extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        // Create `user_security` table if it does not already exist
        if (!$this->hasTable('user_security')) {
            $table = $this->table('user_security', [
                'id' => false,
                'primary_key' => ['user_id'],
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);

            $table
                ->addColumn('user_id', 'integer', ['signed' => false])
                ->addColumn('question_1', 'string', ['limit' => 255, 'null' => true, 'default' => null])
                ->addColumn('answer_1_hash', 'string', ['limit' => 255, 'null' => true, 'default' => null])
                ->addColumn('question_2', 'string', ['limit' => 255, 'null' => true, 'default' => null])
                ->addColumn('answer_2_hash', 'string', ['limit' => 255, 'null' => true, 'default' => null])
                ->addForeignKey('user_id', 'users', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_security_user'
                ])
                ->create();
        }
    }
}
