<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MakeWarGoalsNullable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     */
    public function change(): void
    {
        $table = $this->table('wars');
        $table->changeColumn('goal_key', 'string', ['null' => true])
              ->changeColumn('goal_threshold', 'integer', ['null' => true])
              ->save(); // 'save()' is correct for altering columns, 'update()' is for rows.
    }
}