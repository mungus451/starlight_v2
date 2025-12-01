<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateBounties extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('bounties')) {
            $table = $this->table('bounties', [
                'id' => 'id',
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);

            $table
                ->addColumn('target_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('placer_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('amount', 'decimal', ['precision' => 19, 'scale' => 4, 'null' => false, 'comment' => 'Amount in Naquadah Crystals'])
                ->addColumn('status', 'enum', ['values' => ['active', 'claimed', 'expired'], 'default' => 'active', 'null' => false])
                ->addColumn('claimed_by_id', 'integer', ['signed' => false, 'null' => true, 'default' => null])
                ->addTimestamps()
                
                // Indexes
                ->addIndex(['target_id', 'status'], ['name' => 'idx_target_active'])
                ->addIndex(['status', 'amount'], ['name' => 'idx_high_value_bounties'])
                
                // Foreign Keys
                ->addForeignKey('target_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('placer_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('claimed_by_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                
                ->create();
        }
    }
}