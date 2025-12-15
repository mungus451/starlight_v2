<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateDoctrineDefinitionsTable extends AbstractMigration
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
        $table = $this->table('doctrine_definitions', ['signed' => false]);
        $table->addColumn('name', 'string', ['limit' => 255])
              ->addColumn('description', 'text')
              ->addColumn('type', 'enum', ['values' => ['economy', 'military', 'espionage']])
              ->addColumn('effect', 'string', ['limit' => 255]) // e.g., 'credit_income_bonus'
              ->addColumn('value', 'float') // e.g., 0.05 for a 5% bonus
              ->addTimestamps()
              ->addIndex(['type'])
              ->create();
    }

    public function down(): void
    {
        $this->table('doctrine_definitions')->drop()->save();
    }
}
