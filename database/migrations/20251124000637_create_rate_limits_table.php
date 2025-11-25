<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRateLimitsTable extends AbstractMigration
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
        // Create `rate_limits` table if it does not already exist
        if (!$this->hasTable('rate_limits')) {
            $table = $this->table('rate_limits', [
                'id' => false,
                'primary_key' => ['client_hash', 'route_uri'],
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);

            $table
                ->addColumn('client_hash', 'string', ['limit' => 64, 'null' => false])
                ->addColumn('route_uri', 'string', ['limit' => 191, 'null' => false])
                ->addColumn('request_count', 'integer', ['signed' => false, 'default' => 1, 'null' => false])
                ->addColumn('window_start', 'integer', ['signed' => false, 'null' => false])
                ->addIndex(['window_start'], ['name' => 'idx_window'])
                ->create();
        }
    }
}
