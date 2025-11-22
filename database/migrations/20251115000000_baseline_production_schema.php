<?php

use Phinx\Migration\AbstractMigration;

/**
 * Baseline Migration
 * 
 * This is an empty marker migration representing the existing production schema.
 * It does NOT execute any DDL - it simply marks a point in time where the database
 * was already configured with the V1 schema.
 * 
 * Use scripts/phinx_baseline.php to mark this and any migrations before it as applied
 * without actually running them. This allows you to start using Phinx migrations on
 * an existing production database.
 * 
 * After baselining, all new migrations will run normally.
 */
class BaselineProductionSchema extends AbstractMigration
{
    /**
     * Up Migration - Does nothing for baseline
     * 
     * The actual schema already exists in production. This migration serves only
     * as a marker that can be recorded in phinxlog without executing DDL.
     */
    public function up(): void
    {
        // Intentionally empty - baseline marker only
        // Actual schema already exists in production database
        
        $this->output->writeln('<info>Baseline migration - no changes needed</info>');
    }

    /**
     * Down Migration - Not applicable for baseline
     * 
     * Baselining is a one-way operation. You cannot rollback to "before" the
     * baseline since it represents the initial state.
     */
    public function down(): void
    {
        // Cannot rollback baseline - it represents the starting point
        $this->output->writeln('<error>Cannot rollback baseline migration</error>');
        $this->output->writeln('<comment>Baseline represents the initial production schema</comment>');
    }
}
