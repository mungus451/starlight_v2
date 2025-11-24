<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAllianceForumPosts extends AbstractMigration
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
        // Create `alliance_forum_posts` table if it does not already exist
        if (!$this->hasTable('alliance_forum_posts')) {
            $table = $this->table('alliance_forum_posts', [
                'id' => 'id',
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);

            $table
                ->addColumn('topic_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('alliance_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'Author of the post'])
                ->addColumn('content', 'text', ['null' => false])
                ->addTimestamps(updatedAt: false)
                ->addIndex(['topic_id', 'created_at'], ['name' => 'idx_alliance_forum_posts'])
                ->addForeignKey('topic_id', 'alliance_forum_topics', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_afp_topic'
                ])
                ->addForeignKey('alliance_id', 'alliances', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_afp_alliance'
                ])
                ->addForeignKey('user_id', 'users', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_afp_user'
                ])
                ->create();
        }
    }
}
