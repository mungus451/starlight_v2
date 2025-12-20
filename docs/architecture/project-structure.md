# Project Structure

A high-level map of the codebase with the MVC-S boundaries.

```
starlight_v2/
├── app/
│   ├── Controllers/    # HTTP I/O only (no business logic)
│   ├── Core/           # Database, Session, Config, CSRF, etc.
│   ├── Middleware/     # Auth, Rate limiting, guards
│   ├── Events/         # Domain events
│   ├── Listeners/      # Event handlers
│   ├── Presenters/     # View formatting helpers
│   └── Models/
│       ├── Entities/       # Readonly DTOs
│       ├── Repositories/   # Raw SQL only
│       └── Services/       # Business logic + transactions
├── config/             # Game balance and app settings
├── cron/               # Turn processing, NPCs
├── database/           # Phinx migrations and seeds
├── migrations/         # One-off migration scripts
├── public/             # Front controller (index.php) plus assets and diagnostic info.php
├── tests/              # Verification and tests
└── views/              # Dumb templates
```

Key rules:
- Controllers: HTTP concerns only (validate CSRF, call services, render/redirect)
- Services: All business logic; orchestrate repositories; always wrap multi-table ops in transactions
- Repositories: Parameterized SQL only; no business logic
- Entities: Readonly data containers; transform with `fromArray()`/`toArray()`
- Views: Presentation only; no SQL or business logic
