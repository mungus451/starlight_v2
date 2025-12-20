# Request Lifecycle

How a request flows through the system.

1. Router (FastRoute) maps URI to `Controller::method` via `public/index.php`.
2. Middleware validates authentication, permissions, and rate limits.
3. Controller validates CSRF/input, delegates to a Service, sets flash/redirects.
4. Service loads config, applies business rules, wraps changes in a DB transaction, and orchestrates multiple repositories.
5. Repositories execute parameterized SQL and return Entities.
6. Controller renders a view with data; views remain presentation-only.

## Sequence at a Glance

| Layer | File/Component | Responsibility |
|------|-----------------|----------------|
| Router | `public/index.php` | Map URI → Controller::method |
| Middleware | `App\\Middleware\\*` | Auth, rate limiting, permissions |
| Controller | `App\\Controllers\\*` | HTTP concerns only; no SQL or business logic |
| Service | `App\\Models\\Services\\*` | Business rules + transactions |
| Repository | `App\\Models\\Repositories\\*` | Parameterized SQL only |
| Entity | `App\\Models\\Entities\\*` | Readonly data containers |
| View | `views/*` | Render UI; include CSRF token |
