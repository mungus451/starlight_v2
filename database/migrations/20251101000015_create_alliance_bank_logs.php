<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAllianceBankLogs extends AbstractMigration
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
        // Create `alliance_bank_logs` table if it does not already exist
        if (!$this->hasTable('alliance_bank_logs')) {
            $table = $this->table('alliance_bank_logs', [
                'id' => 'id',
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);

            $table
                ->addColumn('alliance_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('user_id', 'integer', ['signed' => false, 'null' => true, 'default' => null, 'comment' => 'User involved (donator, taxee)'])
                ->addColumn('log_type', 'string', ['limit' => 50, 'null' => false, 'comment' => "e.g., 'donation', 'battle_tax', 'interest'"])
                ->addColumn('amount', 'biginteger', ['null' => false, 'comment' => 'Positive (income) or negative (expense)'])
                ->addColumn('message', 'string', ['limit' => 255, 'null' => false])
                ->addTimestamps(updatedAt: false)
                ->addIndex(['alliance_id', 'created_at'], ['name' => 'idx_alliance_logs'])
                ->addForeignKey('alliance_id', 'alliances', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_bank_log_alliance'
                ])
                ->addForeignKey('user_id', 'users', 'id', [
                    'delete' => 'SET_NULL',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_bank_log_user'
                ])
                ->create();
        }
    }
}
