---
name: Phinx Schema Architect

description: The guardian of the database. This module manages schema evolution using **Phinx**. It enforces a strict "Forward-Only" migration policy, ensuring data integrity, reversibility, and alignment with the `starlight_v2` relational standards (e.g., unsigned integers for IDs, strict foreign keys). Activate this skill when the user requests **Database Changes**, **New Tables**, or **Column Modifications**.
---

# 📜 Procedural Guidance

### Rule 1: The Immutable History Doctrine
*   **Zero-Edit Policy:** **NEVER** edit an existing file in `database/migrations/`. History is immutable.
*   **Forward-Only:** To undo a change, create a **new** migration that reverses the previous logic.
*   **Naming Convention:** All files must follow `YYYYMMDDHHMMSS_DescriptionInSnakeCase.php`.

### Rule 2: Schema Standards (The V2 Spec)
*   **Strict Types:** All migration files must begin with `declare(strict_types=1);`.
*   **Class Structure:** Use `final class` extending `AbstractMigration`.
*   **IDs & Foreign Keys:**
    *   All Primary Keys (`id`) and Foreign Keys (`user_id`, `alliance_id`) must be `['signed' => false]`.
    *   Foreign Keys must define `delete` behavior (usually `CASCADE` for owned data, `SET_NULL` for loose associations).
*   **Engine:** Explicitly set `'engine' => 'InnoDB', 'encoding' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci'` for new tables.

### Rule 3: The Legacy Firewall
*   **Banned Resources:** Explicitly reject any attempt to add columns for:
    *   `naquadah` / `naquadah_crystals`
    *   `dark_matter`
    *   `protoform`
    *   `untraceable_chips`
*   **Rationale:** The economy has been refactored. These are dead assets.

### Rule 4: Implementation Workflow
1.  **The Plan:** Briefly state the change (e.g., "Adding `is_active` boolean to `users` table").
2.  **The Code:** Generate the full PHP class. Prefer the `change()` method for automatic reversibility.
3.  **The Safety Check:**
    *   Does `up()` create data? If so, `down()` must truncate or delete it.
    *   Are indexes added for performance on foreign keys?

---

## 🧪 Example Phinx Pattern (Starlight V2 Standard)

```php
<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSystemAlerts extends AbstractMigration
{
    public function change(): void
    {
        // Always check existence for robustness
        $table = $this->table('system_alerts');
        
        if (!$table->exists()) {
            $table->addColumn('title', 'string', ['limit' => 100])
                  ->addColumn('severity', 'enum', ['values' => ['low', 'medium', 'critical']])
                  ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                  ->addIndex(['severity']) // Performance indexing
                  ->create();
        }
    }
}
```