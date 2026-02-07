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

## ⚡ Visual Style

This project uses a hybrid styling approach combining a foundational CSS file with utility-first classes from Tailwind CSS.

*   **Base Stylesheet:** `example.css` provides the core visual elements, such as colors, component base styles (`.content-box`), and typography.
*   **Utility Classes:** Tailwind CSS is used for layout, spacing, and responsive design adjustments directly within the HTML.
*   **Reference Implementation:** The `training-example.php` file serves as the canonical example of how these two systems should be integrated to create the desired user interface.

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
  - **User Preference:** When proposing UI/UX changes or new application features, prioritize visual wireframes over code snippets for better clarity and visualization.
  - When key technologies aren't specified, prefer the following:

### 2. Database Modifications
*   **Phinx Migrations:** For any database schema changes, always create a new Phinx migration. Do NOT delete existing migration files.

## ⚙️ Operational Guidelines

### 1. Git Workflow
*   **Commit Messages:** When committing changes via the CLI, prefer single-line commit messages with `git commit -m "Your message"`. Multiline messages using heredoc or similar syntax can sometimes be misinterpreted by the underlying shell, leading to commit failures.

### 2. PHP Development
*   **Object Property Access:** Always use the `->` operator to access properties and methods of objects in PHP (e.g., `$object->property`, `$object->method()`). Do NOT use the `.` operator, as it is reserved for string concatenation and will result in syntax errors when used for object access.

---
# Starlight Dominion - Deprecated Features

This document tracks features that have been removed or deprecated from the game's configuration.

## Resources

### Protoform
- **Description:** A biological resource required for elite units and general upkeep.
- **Removed In:** `8a9f219`
- **Details:**
    - Removed from `upkeep` costs in `config/game_balance.php`.
    - Removed `protoform_steal_rate` from spy missions.
    - Removed the `protoform_vat` structure and its benefits.

### Crystals (Naquadah) & Dark Matter
- **Description:** Premium resources used for advanced structures, high-tier armory items, and Black Market services.
- **Removed In:** `369a0dc`, `b465a33`
- **Details:**
    - All `cost_crystals` and `cost_dark_matter` fields were removed from `config/armory_items.php`.
    - `base_crystal_cost` and `base_dark_matter_cost` were removed from structures in `config/game_balance.php`.
    - Steal rates (`crystal_steal_rate`, `dark_matter_steal_rate`) were removed.
    - Production benefits (`dark_matter_per_siphon_level`, `naquadah_per_mining_complex_level`) were removed.

## Game Systems

### Alliance Sidebar (Uplink)
- **Description:** A dedicated sidebar providing alliance-specific information and quick actions.
- **Removed In:** `4272e40425e39437e22114cb19a14b0d95ff2db1`
- **Details:**
    - The entire `<aside class="alliance-uplink">` block was removed from `views/layouts/main.php`.
    - Functionality related to alliance treasury, DEFCON status, war status, objectives, active operations, and intelligence feed is no longer displayed.

### Black Market
- **Description:** A premium feature hub offering various services and items.
- **Removed In:** `369a0dc`
- **Details:**
    - The entire `config/black_market.php` file was deleted.
    - This removed features like stat respecs, turn refills, void containers (loot boxes), and mercenary drafting.

### High-Tier Armory (Tiers 6-10)
- **Description:** Advanced weapon and defense tiers.
- **Removed In:** `b465a33`
- **Details:**
    - All armory items in the "Tier 6-10 Expansion" sections were removed from `config/armory_items.php`.

### Galactic Market
- **Description:** A system for player-to-player resource trading.
- **Removed In:** `40290ad`
- **Details:**
    - The `galactic_market` structure definition was removed from `config/game_balance.php`.

### Embassy & Edicts
- **Description:** A system for implementing empire-wide bonuses through edicts unlocked by the Embassy structure.
- **Removed In:** `9ee28ac`
- **Details:**
    - The `embassy` structure was removed from `config/game_balance.php`. The `config/edicts.php` file remains, but the core structure enabling the feature is gone.

## Structures

The following structures were removed to simplify gameplay and streamline the economy.

- **Removed In:** `9ee28ac`
- **Structure List:**
    - `fortification`
    - `offense_upgrade`
    - `defense_upgrade`
    - `spy_upgrade`
    - `accounting_firm`
    - `quantum_research_lab`
    - `nanite_forge`
    - `dark_matter_siphon`
    - `naquadah_mining_complex`
    - `protoform_vat`
    - `weapon_vault`
    - `fusion_plant`
    - `orbital_trade_port`
    - `banking_datacenter`
    - `cloning_vats`
    - `war_college`
    - `phase_bunker`
    - `ion_cannon_network`