<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class FixAllianceBankOverflow extends AbstractMigration
{
    public function change(): void
    {
        // Use raw SQL to ensure correct conversion from BIGINT UNSIGNED to DECIMAL
        // DECIMAL(60, 0) is large enough to hold the max BIGINT UNSIGNED value (20 digits) easily.
        
        // 1. Modify `alliances` table `bank_credits`
        // We make it SIGNED (default) to align with the PHP float logic, but it will hold positive values.
        $this->execute("ALTER TABLE `alliances` MODIFY COLUMN `bank_credits` DECIMAL(60, 0) NOT NULL DEFAULT 0");

        // 2. Modify `alliance_bank_logs` table `amount`
        $this->execute("ALTER TABLE `alliance_bank_logs` MODIFY COLUMN `amount` DECIMAL(60, 0) NOT NULL");
    }
}
