<?php

use Phinx\Migration\AbstractMigration;

class CreateRealmNewsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('realm_news', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'integer', ['identity' => true])
              ->addColumn('title', 'string', ['limit' => 255])
              ->addColumn('content', 'text')
              ->addColumn('is_active', 'boolean', ['default' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->create();
    }
}
