<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddProtoformToSpyReports extends AbstractMigration
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
        $table = $this->table('spy_reports');
        $table->addColumn('protoform_stolen', 'decimal', ['precision' => 19, 'scale' => 4, 'default' => 0, 'after' => 'dark_matter_stolen'])
              ->addColumn('protoform_seen', 'decimal', ['precision' => 19, 'scale' => 4, 'null' => true, 'default' => null, 'after' => 'dark_matter_seen'])
              ->update();
    }
}