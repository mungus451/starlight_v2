<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RemoveProtoformFeature extends AbstractMigration
{
    public function up(): void
    {
        $this->table('user_resources')
            ->removeColumn('protoform')
            ->save();

        $this->table('spy_reports')
            ->removeColumn('protoform_stolen')
            ->removeColumn('protoform_seen')
            ->save();
    }

    public function down(): void
    {
        $this->table('user_resources')
            ->addColumn('protoform', 'float', ['default' => 0.0])
            ->save();

        $this->table('spy_reports')
            ->addColumn('protoform_stolen', 'decimal', ['precision' => 19, 'scale' => 4, 'default' => 0])
            ->addColumn('protoform_seen', 'decimal', ['precision' => 19, 'scale' => 4, 'null' => true, 'default' => null])
            ->save();
    }
}