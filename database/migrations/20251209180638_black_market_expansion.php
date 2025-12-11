<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class BlackMarketExpansion extends AbstractMigration
{
    public function change(): void
    {
        // 1. User Active Effects Table (Buffs/Debuffs)
        if (!$this->hasTable('user_active_effects')) {
            $effects = $this->table('user_active_effects');
            $effects->addColumn('user_id', 'integer', ['signed' => false]) // Must match users.id (unsigned)
                ->addColumn('effect_type', 'string', ['limit' => 50]) // jamming, peace_shield, wounded
                ->addColumn('expires_at', 'datetime')
                ->addColumn('metadata', 'json', ['null' => true]) // For extra data like 'wounded_by_user_id'
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addIndex(['user_id', 'effect_type']) // Fast lookup for specific user effects
                ->create();
        }

        // 2. Intel Listings Table (Spy Report Marketplace)
        if (!$this->hasTable('intel_listings')) {
            $intel = $this->table('intel_listings');
            $intel->addColumn('seller_id', 'integer', ['signed' => false]) // Must match users.id (unsigned)
                ->addColumn('report_id', 'integer', ['signed' => false]) // spy_reports.id is likely unsigned too
                ->addColumn('price', 'decimal', ['precision' => 15, 'scale' => 2]) // Crystal cost
                ->addColumn('is_sold', 'boolean', ['default' => false])
                ->addColumn('buyer_id', 'integer', ['signed' => false, 'null' => true]) // Must match users.id (unsigned)
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addForeignKey('seller_id', 'users', 'id', ['delete' => 'CASCADE'])
                ->addForeignKey('report_id', 'spy_reports', 'id', ['delete' => 'CASCADE'])
                ->addIndex(['is_sold', 'created_at']) // For listing open market items
                ->create();
        }

        // 3. User Resources: Untraceable Chips
        $table = $this->table('user_resources');
        if (!$table->hasColumn('untraceable_chips')) {
            $table->addColumn('untraceable_chips', 'biginteger', ['default' => 0, 'after' => 'banked_credits'])
                  ->update();
        }

        // 4. User Stats: Syndicate Reputation
        $stats = $this->table('user_stats');
        if (!$stats->hasColumn('syndicate_reputation')) {
            $stats->addColumn('syndicate_reputation', 'integer', ['default' => 0, 'after' => 'war_prestige'])
                  ->update();
        }
    }
}