<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUserDoctrinesTable extends AbstractMigration
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
    public function up(): void
    {
        // Ensure parent tables exist first
        if (!$this->hasTable('users') || !$this->hasTable('doctrine_definitions')) {
            return;
        }

        // Align parent PKs with FK requirements (unsigned ids)
        $this->execute('ALTER TABLE `users` MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT');
        $this->execute('ALTER TABLE `doctrine_definitions` MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT');

        if (!$this->hasTable('user_doctrines')) {
            $table = $this->table('user_doctrines', [
                'engine' => 'InnoDB',
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);
            $table->addColumn('user_id', 'integer', ['signed' => false])
                ->addColumn('doctrine_id', 'integer', ['signed' => false])
                ->addTimestamps()
                ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('doctrine_id', 'doctrine_definitions', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addIndex(['user_id', 'doctrine_id'], ['unique' => true])
                ->create();
        }
    }

    public function down(): void
    {
        $this->table('user_doctrines')->drop()->save();
    }
}
