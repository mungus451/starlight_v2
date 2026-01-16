<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddAllianceDirectives extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('alliances');
        
        $table->addColumn('directive_type', 'string', ['null' => true, 'default' => null, 'limit' => 50, 'after' => 'last_compound_at'])
              ->addColumn('directive_target', 'biginteger', ['signed' => false, 'default' => 0, 'after' => 'directive_type'])
              ->addColumn('directive_start_value', 'biginteger', ['signed' => false, 'default' => 0, 'after' => 'directive_target'])
              ->addColumn('directive_started_at', 'timestamp', ['null' => true, 'default' => null, 'after' => 'directive_start_value'])
              ->addColumn('completed_directives', 'json', ['null' => true, 'after' => 'directive_started_at'])
              ->update();
    }
}