<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAllianceLoans extends AbstractMigration
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
        // Create `alliance_loans` table if it does not already exist
        if (!$this->hasTable('alliance_loans')) {
            $table = $this->table('alliance_loans', [
                'id' => 'id',
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);

            $table
                ->addColumn('alliance_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'The user (borrower) requesting the loan'])
                ->addColumn('amount_requested', 'biginteger', ['signed' => false, 'null' => false])
                ->addColumn('amount_to_repay', 'biginteger', ['signed' => false, 'default' => 0, 'null' => false])
                ->addColumn('status', 'enum', ['values' => ['pending', 'active', 'paid', 'denied'], 'default' => 'pending', 'null' => false])
                ->addTimestamps()
                ->addIndex(['alliance_id', 'status'], ['name' => 'idx_alliance_loans_status'])
                ->addIndex(['user_id', 'status'], ['name' => 'idx_user_loans'])
                ->addForeignKey('alliance_id', 'alliances', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_loan_alliance'
                ])
                ->addForeignKey('user_id', 'users', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_loan_user'
                ])
                ->create();
        }
    }
}
