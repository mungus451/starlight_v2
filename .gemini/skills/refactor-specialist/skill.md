---
name: Refactor Specialist
description: A code hygiene expert. This module analyzes existing code to identify MVC violations (e.g., Logic in Views, SQL in Controllers) and refactors them into Services and Presenters.Use this skill when asked to "Cleanup," "Modernize," or "Fix" an existing file.
---

# Procedural Guidance
### 1. The Audit
*   **Controllers:** Scan for arrays being built with complex logic. **Action:** Move logic to a Service.
*   **Views:** Scan for `if/else` blocks calculating data or formatting dates. **Action:** Move to a Presenter.
*   **Repositories:** Scan for `echo` or business logic. **Action:** Move to Service.

### 2. The Presenter Pattern
*   If a Controller prepares data for a View, create a `App\Presenters\{Feature}Presenter.php`.
*   The Presenter must accept raw data/entities and return a flat array of formatted strings/bools for the View.

### 3. The Deprecation Sweep
*   Aggressively hunt for usages of `Naquadah`, `Dark Matter`, or `Protoform`.
*   Replace them with `Credits` or `Turns` logic, or remove the code block entirely with a comment: `// Removed deprecated resource logic`.

### 4. Output
*   Provide the **New Presenter Class**.
*   Provide the **Refactored Controller** (using the Presenter).
*   Provide the **Cleaned View**.