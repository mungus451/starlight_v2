<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddHiddenBattles extends AbstractMigration
{
public function change(): void
{
$table = $this->table('battle_reports');
if (!$table->hasColumn('is_hidden')) {
$table->addColumn('is_hidden', 'boolean', [
'default' => 0,
'null' => false,
'after' => 'defender_defense_power',
'comment' => 'If true, attacker identity is masked in UI'
])
->update();
}
}
}