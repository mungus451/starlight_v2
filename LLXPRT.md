# 🌌 Starlight Dominion V2 | Technical Reference & Design Patterns

## 🎨 UI/UX Component Standards

### 1. Navigation Tabs
**Context:** Used for Armory, Almanac, and multi-view interfaces.
**Requirement:** Requires `initTabs()` to handle the `.active` class toggle.

**HTML Structure:**
```html
<!-- Nav Container -->
<div class="tabs-nav mb-4 justify-content-center">
    <a class="tab-link active" data-tab="tab-id-1">Title 1</a>
    <a class="tab-link" data-tab="tab-id-2">Title 2</a>
</div>

<!-- Content Container -->
<div id="tab-id-1" class="tab-content active">...</div>
<div id="tab-id-2" class="tab-content">...</div>
```

### 2. Cards & Grid Layouts
**Context:** The standard for Structures, Almanac dossiers, and inventory items.

**Container:** `.structures-grid`
```css
display: grid;
grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
gap: 1.5rem;
```

**Card Component:** `.structure-card`
*   **Styling:** Background `rgba(13, 17, 23, 0.85)`, backdrop-filter blur, subtle border.
*   **Interaction:** `translateY(-2px)` and increased shadow on hover.
*   **Internal Parts:**
    *   `.card-header-main`: Flex container for `.card-icon` and `.card-title-group`.
    *   `.card-body-main`: Primary content area.
    *   `.card-footer-actions`: Button and interaction area.

### 3. Form Elements
**Context:** Standard dark-theme inputs for consistency.

```html
<select class="form-select bg-dark text-light border-secondary">
    <option value="" selected disabled>-- Select --</option>
    <!-- Options -->
</select>
```

## ⚡ Visual Styles (Neon Glitch Theme)

| Class | Effect | Requirements |
| :--- | :--- | :--- |
| `.text-neon-blue` | Color: `#00f3ff`, Text shadow: `0 0 10px rgba(0, 243, 255, 0.7)` | |
| `.border-neon` | Border: `#00f3ff`, Box shadow: `0 0 15px rgba(0, 243, 255, 0.2)` | |
| `.glitch-text` | High-tech distortion | Requires `data-text="Same Text"` for pseudo-elements |
| `.scanner-line` | Animated overlay | Typically used on avatars or status images |

## 🛠 Infrastructure & Routing

### 1. Avatar Resolution
*   **Player Routes:** `/serve/avatar/{filename}`
*   **Alliance Routes:** `/serve/alliance_avatar/{filename}`
*   **Fallback Logic:** If `profile_picture_url` is null, use the standard placeholder.

### 2. Mobile Pagination (FastRoute)
To avoid 404 errors, follow the strict URL structure where the page is a path and the limit is a query string.

*   **Correct Format:** `/battle/page/{number}?limit={limit}` (e.g., `/battle/page/1?limit=10`)
*   **Controller Implementation:**
    *   Fetch page from the router path.
    *   Fetch limit from `$_GET['limit']`.
*   **Note:** Never use `/battle/page/1/10` as the router is not configured for multiple path parameters in pagination.

## 📜 Maintenance Protocols

### 📂 Glossary Synchronization
**Trigger:** Whenever a Structure, Unit, Item, Resource, or Mechanic is added or changed.

1.  **Configuration:** Update `description` and `notes` fields in `config/game_balance.php` or `config/armory_items.php`.
2.  **Controller Logic:** Update `app/Controllers/GlossaryController.php` for new categories or resource types.
3.  **View Layer:** Map icons and visual categories in `views/glossary/index.php`.
4.  **Requirement:** The `/glossary` route must reflect 100% accuracy relative to the current codebase.

### 1. Workflow Standards
 2 *   **Plan Verification:** Before implementing any new feature, bug fix, or significant logic change, always provide a concise summary of the implementation plan and explain the underlying logic to the
     user for approval.