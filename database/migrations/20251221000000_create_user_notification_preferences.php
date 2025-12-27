<?php

use Phinx\Migration\AbstractMigration;

/**
 * Migration: Add User Notification Preferences Table
 * 
 * Creates a table to store user preferences for notification types.
 * Each user can customize which notification types they want to receive.
 */
final class CreateUserNotificationPreferences extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('user_notification_preferences', [
            'id' => false,
            'primary_key' => ['user_id']
        ]);
        
        $table->addColumn('user_id', 'integer', [
                'signed' => false,
                'null' => false
            ])
            ->addColumn('attack_enabled', 'boolean', [
                'default' => true,
                'null' => false
            ])
            ->addColumn('spy_enabled', 'boolean', [
                'default' => true,
                'null' => false
            ])
            ->addColumn('alliance_enabled', 'boolean', [
                'default' => true,
                'null' => false
            ])
            ->addColumn('system_enabled', 'boolean', [
                'default' => true,
                'null' => false
            ])
            ->addColumn('push_notifications_enabled', 'boolean', [
                'default' => false,
                'null' => false
            ])
            ->addColumn('created_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false
            ])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'null' => false
            ])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_notif_prefs_user'
            ])
            ->create();
    }
}
