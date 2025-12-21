<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateNotificationsTable extends AbstractMigration
{
    public function change(): void
    {
        // Only create if missing to stay compatible with existing schema deployments
        if ($this->hasTable('notifications')) {
            return;
        }

        $table = $this->table('notifications', [
            'id' => 'id',
            'signed' => false,
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);

        $table
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('type', 'enum', ['values' => ['attack', 'spy', 'alliance', 'system'], 'null' => false])
            ->addColumn('title', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('message', 'text', ['null' => false])
            ->addColumn('link', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('is_read', 'boolean', ['null' => false, 'default' => 0])
            ->addColumn('created_at', 'timestamp', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['user_id', 'is_read'], ['name' => 'idx_user_read'])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete'     => 'CASCADE',
                'update'     => 'NO_ACTION',
                'constraint' => 'fk_notification_user',
            ])
            ->create();
        // Index for paginating recent notifications: user_id + created_at DESC
        // NOTE: Our pinned Phinx version does not support specifying DESC index direction via the fluent API.
        // To keep this migration consistent with the existing production schema (which relies on a DESC index
        // for efficient "most recent first" pagination) we intentionally use raw SQL here instead of the
        // table abstraction. Changing this to a default ASC index would alter query plans and performance.
        $this->execute('ALTER TABLE `notifications` ADD INDEX `idx_user_created` (`user_id`, `created_at` DESC)');
    }
}
