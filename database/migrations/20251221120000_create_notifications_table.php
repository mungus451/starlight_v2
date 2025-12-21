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
            'engine' => 'InnoDB',
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
            ->create();

        // Add FK to users(id) with cascade delete
        $this->table('notifications')
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION', 'constraint' => 'fk_notification_user'])
            ->save();

        // Index for paginating recent notifications: user_id + created_at DESC
        // Phinx doesn't expose DESC index direction options, so use raw SQL to match schema exactly.
        $this->execute('ALTER TABLE `notifications` ADD INDEX `idx_user_created` (`user_id`, `created_at` DESC)');
    }
}
