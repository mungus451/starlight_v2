<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAllianceApplications extends AbstractMigration
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
        // Create `alliance_applications` table if it does not already exist
        if (!$this->hasTable('alliance_applications')) {
            $table = $this->table('alliance_applications', [
                'id' => 'id',
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);

            $table
                ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('alliance_id', 'integer', ['signed' => false, 'null' => false])
                ->addTimestamps(updatedAt: false)
                ->addIndex(['user_id', 'alliance_id'], ['unique' => true, 'name' => 'uk_user_alliance'])
                ->addForeignKey('user_id', 'users', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_app_user'
                ])
                ->addForeignKey('alliance_id', 'alliances', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_app_alliance'
                ])
                ->create();
        }
    }
}
