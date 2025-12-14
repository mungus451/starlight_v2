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
        if (!$this->hasTable('user_doctrines')) {
            $table = $this->table('user_doctrines');
            $table->addColumn('user_id', 'integer', ['signed' => false])
                  ->addColumn('doctrine_id', 'integer', ['signed' => true])
                  ->addTimestamps()
                  ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                  ->addForeignKey('doctrine_id', 'doctrine_definitions', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                  ->addIndex(['user_id', 'doctrine_id'], ['unique' => true])
                  ->create();
        }
    }

    public function down(): void
    {
        $this->table('user_doctrines')->drop()->save();
    }
}
