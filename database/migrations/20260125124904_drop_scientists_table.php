<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DropScientistsTable extends AbstractMigration
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
        $this->table('scientists')->drop()->save();
    }

    public function down(): void
    {
        $table = $this->table('scientists');
        $table->addColumn('user_id', 'integer', ['null' => false])
              ->addColumn('is_active', 'boolean', ['default' => true, 'null' => false])
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['user_id'], ['unique' => false])
              ->create();
    }}
