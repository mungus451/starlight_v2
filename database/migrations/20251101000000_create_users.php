<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsers extends AbstractMigration
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
        // Create `users` table if it does not already exist
        if (!$this->hasTable('users')) {
            $table = $this->table('users', [
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);

            $table
                ->addColumn('email', 'string', ['limit' => 191])
                ->addColumn('character_name', 'string', ['limit' => 50])
                ->addColumn('bio', 'text', ['null' => true, 'default' => null])
                ->addColumn('profile_picture_url', 'string', ['limit' => 255, 'null' => true, 'default' => null])
                ->addColumn('phone_number', 'string', ['limit' => 25, 'null' => true, 'default' => null])
                ->addColumn('password_hash', 'string', ['limit' => 255])
                ->addTimestamps(updatedAt: false)
                ->addIndex(['email'], ['unique' => true, 'name' => 'uk_email'])
                ->addIndex(['character_name'], ['unique' => true, 'name' => 'uk_character_name'])
                ->create();
        }
    }
}
