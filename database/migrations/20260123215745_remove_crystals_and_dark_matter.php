<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RemoveCrystalsAndDarkMatter extends AbstractMigration
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
        $this->table('user_resources')
            ->removeColumn('naquadah_crystals')
            ->removeColumn('dark_matter')
            ->removeColumn('untraceable_chips')
            ->save();

        $this->table('spy_reports')
            ->removeColumn('naquadah_crystals_stolen')
            ->removeColumn('dark_matter_stolen')
            ->removeColumn('naquadah_crystals_seen')
            ->removeColumn('dark_matter_seen')
            ->save();

        if ($this->hasTable('black_market_logs')) {
            $this->table('black_market_logs')->drop()->save();
        }
    }

    public function down(): void
    {
        $this->table('user_resources')
            ->addColumn('naquadah_crystals', 'decimal', ['precision' => 19, 'scale' => 4, 'default' => 0.0000])
            ->addColumn('dark_matter', 'decimal', ['precision' => 19, 'scale' => 4, 'default' => 0.0000])
            ->addColumn('untraceable_chips', 'integer', ['default' => 0])
            ->save();

        $this->table('spy_reports')
            ->addColumn('naquadah_crystals_stolen', 'decimal', ['precision' => 19, 'scale' => 4, 'null' => true])
            ->addColumn('dark_matter_stolen', 'integer', ['null' => true])
            ->addColumn('naquadah_crystals_seen', 'decimal', ['precision' => 19, 'scale' => 4, 'null' => true])
            ->addColumn('dark_matter_seen', 'integer', ['null' => true])
            ->save();

        if (!$this->hasTable('black_market_logs')) {
            $this->table('black_market_logs')
                ->addColumn('user_id', 'integer', ['signed' => false])
                ->addColumn('item_key', 'string')
                ->addColumn('cost_credits', 'biginteger', ['default' => 0])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->create();
        }
    }
}
