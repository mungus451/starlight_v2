<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateBlackMarketLogs extends AbstractMigration
{
public function change(): void
{
if (!$this->hasTable('black_market_logs')) {
$table = $this->table('black_market_logs', [
'id' => 'id',
'signed' => false,
'collation' => 'utf8mb4_unicode_ci',
'encoding' => 'utf8mb4',
]);

$table
->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
->addColumn('action_type', 'string', ['limit' => 50, 'null' => false, 'comment' => "e.g., 'conversion', 'purchase', 'bounty', 'shadow_contract'"])
->addColumn('item_key', 'string', ['limit' => 50, 'null' => true, 'default' => null, 'comment' => "Specific item bought or NULL for conversions"])
->addColumn('cost_currency', 'enum', ['values' => ['credits', 'crystals'], 'null' => false])
->addColumn('cost_amount', 'decimal', ['precision' => 19, 'scale' => 4, 'null' => false])
->addColumn('details_json', 'json', ['null' => true, 'default' => null, 'comment' => "Metadata: loot results, target IDs, etc."])
->addTimestamps(updatedAt: false)

// Foreign Key
->addForeignKey('user_id', 'users', 'id', [
'delete' => 'CASCADE',
'update' => 'NO_ACTION',
'constraint' => 'fk_bm_log_user'
])

// Indexes for Admin/History lookups
->addIndex(['user_id', 'created_at'], ['name' => 'idx_bm_user_history'])
->addIndex(['action_type'], ['name' => 'idx_bm_action_type'])

->create();
}
}
}