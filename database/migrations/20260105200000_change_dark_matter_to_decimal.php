<?php

use Phinx\Migration\AbstractMigration;

class ChangeDarkMatterToDecimal extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('user_resources');
        $table->changeColumn('dark_matter', 'decimal', [
            'precision' => 40,
            'scale' => 18,
            'default' => 0.000000000000000000,
            'signed' => false 
                              // Phinx/MySQL decimal is signed.
        ])->update();
    }
}
