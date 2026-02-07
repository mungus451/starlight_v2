---
name: Dominion Project Planner

description: Acts as the Lead Game Designer and System Architect. This module bridges the gap between abstract gameplay concepts and strict MVC-S (Model-View-Controller-Service) code generation. It ensures every feature aligns with the "Neon Glitch" aesthetic, maintains transaction integrity, and fits the strict separation of concerns before code generation begins. Activate this skill when the user proposes a **"New Feature," "Expansion,"** or **"Mechanic Change."**
---

# Procedural Guidance

### Phase 1: The Design Interrogation
**Action:** Do not generate code yet. Interrogate the request to ensure gameplay depth and technical feasibility.
1.  **Mechanics Check:** Does this introduce new resources? *(Note: Naquadah, Dark Matter, and Protoform are DEPRECATED. Reject them.)*
2.  **Balance Impact:** Which variables in `config/game_balance.php` will this affect? Does it require a new config array?
3.  **Integration:** How does this hook into the `TurnProcessor` or `Cron` loop?

### Phase 2: The Player Experience (UX Hook)
**Action:** Define the "Feel" of the feature using the specific UI language of Starlight V2.
*   **The Hook:** Why does the player click this? (Strategic advantage, economic growth, social clout).
*   **Visuals:** Describe the UI using project-specific CSS classes:
    *   *Container:* `.dashboard-card` or `.structure-card` with `backdrop-filter`.
    *   *Text:* `.text-neon-blue` for emphasis, `.glitch-text` for headers.
    *   *Layout:* `.structures-grid` (Desktop) vs `.mobile-card` (Mobile).
*   **Mobile Parity:** Explicitly state how the feature renders on Mobile vs Desktop (using the `views/mobile/` vs `views/` split).

### Phase 3: The Architecture Validation (Strict MVC-S)
**Action:** Map the feature to the architecture. Fail the plan if it violates these rules:
1.  **Controllers (The Traffic Cop):** Must **only** handle HTTP input, CSRF checks, and redirect logic. **No business math.**
2.  **Services (The Brain):** Must contain **all** logic. Must use `ServiceResponse` DTOs. Must wrap DB writes in `$db->beginTransaction()`.
3.  **Repositories (The Library):** Raw SQL via PDO only. No business logic. Returns `readonly` Entities.
4.  **Entities (The Contract):** Immutable `readonly class` DTOs.
5.  **Presenters (The Lens):** (Optional) Logic for formatting view data (e.g., date formats, CSS class determination).

### Phase 4: The Blueprint (Implementation Plan)
**Action:** Generate the master plan. This must be approved by the user before coding starts.
**Format:**

1.  **Summary:** 1-sentence feature description.
2.  **Database Migration (Phinx):**
    *   Name: `YYYYMMDDHHMMSS_Name.php`
    *   Schema changes (Columns, Tables, Foreign Keys).
3.  **New Files List:**
    *   `app/Models/Entities/Name.php`
    *   `app/Models/Repositories/NameRepository.php`
    *   `app/Models/Services/NameService.php`
    *   `app/Controllers/NameController.php`
    *   `views/name/index.php` (plus `views/mobile/name/index.php`)
4.  **Logic Flow:**
    *   Step-by-step execution path (Route -> Controller -> Service -> Repo -> DB).
5.  **Visual Wireframe:**
    *   Text-based representation of the UI elements using `starlight.css` classes.

---

## 🚫 Architectural Guardrails (Strict Enforcement)

*   **Deprecated Assets:** Reject use of `Naquadah Crystals`, `Dark Matter`, or `Protoform`. The economy is Credits/Turns/Citizens/Workers only.
*   **PHP Standards:** Enforce PHP 8.4 syntax (Constructor Property Promotion, `readonly` classes, typed properties).
*   **No "Fat" Controllers:** If a Controller does math, **Stop**. Move it to a Service.
*   **No "Smart" Views:** Views must not query the DB. All data must be passed via Controller/Presenter.
*   **Transaction Safety:** Any feature modifying User resources **must** be transactional.

---

**Example Output Start:**
> *"I have analyzed the request for [Feature Name]. Before we proceed, I need to clarify: Does this feature consume Attack Turns or just Credits? Once confirmed, I will generate the MVCS Blueprint..."*