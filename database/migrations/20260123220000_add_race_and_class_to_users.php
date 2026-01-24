<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddRaceAndClassToUsers extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('users');
        if (!$table->hasColumn('race')) {
            $table->addColumn('race', 'string', ['limit' => 50, 'null' => true, 'default' => null]);
        }
        if (!$table->hasColumn('class')) {
            $table->addColumn('class', 'string', ['limit' => 50, 'null' => true, 'default' => null]);
        }
        $table->update();
    }
}
