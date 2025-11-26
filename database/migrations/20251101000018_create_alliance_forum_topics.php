<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAllianceForumTopics extends AbstractMigration
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
        // Create `alliance_forum_topics` table if it does not already exist
        if (!$this->hasTable('alliance_forum_topics')) {
            $table = $this->table('alliance_forum_topics', [
                'id' => 'id',
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);

            $table
                ->addColumn('alliance_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'Author/Creator of the topic'])
                ->addColumn('title', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('is_locked', 'boolean', ['default' => 0, 'null' => false])
                ->addColumn('is_pinned', 'boolean', ['default' => 0, 'null' => false])
                ->addTimestamps(updatedAt: false)
                ->addColumn('last_reply_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
                ->addColumn('last_reply_user_id', 'integer', ['signed' => false, 'null' => true, 'default' => null])
                ->addIndex(['alliance_id', 'last_reply_at'], ['name' => 'idx_alliance_forum_topics'])
                ->addForeignKey('alliance_id', 'alliances', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_aft_alliance'
                ])
                ->addForeignKey('user_id', 'users', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_aft_user'
                ])
                ->addForeignKey('last_reply_user_id', 'users', 'id', [
                    'delete' => 'SET_NULL',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_aft_last_reply_user'
                ])
                ->create();
        }
    }
}
