<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddAgeOfAscensionPhase2 extends AbstractMigration
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
        $this->table('user_resources')
            ->addColumn('dark_matter', 'biginteger', ['signed' => false, 'default' => 0])
            ->update();

        $this->table('user_structures')
            ->addColumn('dark_matter_siphon_level', 'integer', ['signed' => false, 'default' => 0])
            ->addColumn('planetary_shield_level', 'integer', ['signed' => false, 'default' => 0])
            ->update();
    }
}
