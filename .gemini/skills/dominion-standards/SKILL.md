---
name: Starlight Dominion V2 Code Standards

description: The project's Compliance Officer and Linter. This module enforces the strict **MVC-S-P** (Model-View-Controller-Service-Presenter) architecture, PHP 8.4 typing standards, and the "Neon Glitch" design system. It acts as the final gatekeeper against deprecated logic and spaghetti code. Activate this skill when generating **PHP Controllers**, **Services**, **Views**, or **Database Migrations**.
---

# Procedural Guidance

### Rule 1: PHP 8.4 & Syntax Enforcement
*   **Object Access:** Strictly use the `->` operator for object properties/methods. Never use `.` (concatenation) on objects.
*   **Modern Features:**
    *   Use **Constructor Property Promotion** for all DTOs and Dependency Injection.
    *   Use `readonly class` for all Entities.
    *   Use strict typing (`int`, `string`, `?array`) in all function signatures.
*   **No Magic:** Do not use magic methods (`__get`, `__set`). Properties must be explicit and public readonly.

### Rule 2: The MVC-S-P Architecture
*   **Controllers (Slim):** Handle Request -> Call Service -> Handle `ServiceResponse` -> Render View/Redirect. **Zero** business logic. **Zero** SQL.
*   **Services (Transactional):** Contain all business rules. Must return `App\Core\ServiceResponse`. Multi-step updates must use `$db->beginTransaction()`.
*   **Repositories (SQL Only):** Raw PDO Prepared Statements only. No logic.
*   **Presenters (View Logic):** Use Presenters to format data (dates, CSS classes, badges) before sending to View. Views must be "dumb."

### Rule 3: The "Neon Glitch" UI System
*   **Containers:**
    *   **Desktop:** `.structures-grid`, `.structure-card`, `.card-header-main`, `.card-body-main`.
    *   **Mobile:** `.mobile-content`, `.mobile-card`.
*   **Typography & Color:**
    *   Headers: `.page-title-neon`, `.glitch-text`.
    *   Accents: `.text-neon-blue` (#00f3ff), `.text-warning` (Gold), `.text-danger` (Red).
*   **Interactive Elements:**
    *   Tabs: `.tabs-nav` > `.tab-link` paired with `.tab-content`. (Requires `initTabs()` JS).
    *   Forms: `.btn-submit` inside `.structure-card`.

### Rule 4: Infrastructure & Routing
*   **Phinx Migrations:**
    *   **Immutable:** Never edit existing migration files.
    *   **Additive:** Always create a new file `YYYYMMDDHHMMSS_Description.php` for schema changes.
*   **Pagination:**
    *   Strict Format: `/feature/page/{page}?limit={limit}`.
    *   Never use `/feature/page/{page}/{limit}` (Router incompatibility).

### Rule 5: The Deprecation Firewall
**HARD REJECT:** Do not generate code, DB columns, or UI logic for the following removed features. Auto-correct to **Credits** or **Turns** where applicable.
*   ❌ **Resources:** `Protoform`, `Naquadah` (or Crystals), `Dark Matter`.
*   ❌ **Structures:** `Embassy`, `Black Market`, `Galactic Market`, `High-Tier Armory` (T6+).

---

## 🧪 Example Patterns

**Controller Pattern:**
```php
public function handleAction(): void {
    // 1. Validate
    $data = $this->validate($_POST, ['id' => 'required|int']);
    
    // 2. Service Call
    $response = $this->service->processAction($this->session->get('user_id'), $data['id']);
    
    // 3. Response
    if ($response->isSuccess()) {
        $this->session->setFlash('success', $response->message);
        $this->redirect('/dashboard');
    }
}
```

**View Pattern (Blade/PHP):**
```html
<div class="structures-grid">
    <div class="structure-card">
        <div class="card-header-main">
            <h3 class="text-neon-blue"><?= htmlspecialchars($item->name) ?></h3>
        </div>
    </div>
</div>
```