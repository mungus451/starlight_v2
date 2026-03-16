# Project Guidelines

## Code Style
- Strict MVC-S boundaries: Controllers handle HTTP only, Services contain business logic and transactions, Repositories contain SQL only, Entities are readonly DTOs, Views are presentation-only.
- Keep game mechanics in config files (balance and costs) and read them from Services, not Controllers.
- Repositories use CRUD naming like `findById`, `create`, `update`, `delete`.

## Architecture
- Front controller at `public/index.php` routes via FastRoute into Controllers.
- Dependency injection via PHP-DI container (see `app/Core/ContainerFactory.php`).
- Multi-table writes must be wrapped in transactions in Services.

## Build and Test
- Install deps: `composer install`.
- Run local server: `php -S localhost:8000 -t public`.
- Cron loop: `php cron/process_turn.php`.
- Migrations (local): `composer phinx migrate`.
- Unit tests: `./vendor/bin/phpunit --testsuite Unit`.
- Integration tests: `./vendor/bin/phpunit --testsuite Integration`.
- Compliance suite (docker): `docker compose exec app php tests/Compliance/run_compliance_suite.php`.

## Project Conventions
- Views must include CSRF tokens from `BaseController` (`$csrf_token`).
- Controllers call Services and set flash messages; no SQL, no HTML building.
- Services call Repositories and return `ServiceResponse` objects.
- Entities are immutable and typed with `public readonly` properties.

## Integration Points
- DB: MariaDB/MySQL via PDO, configured in `.env` and loaded in `app/Core/Database.php`.
- Redis: required for sessions and CSRF storage (`app/Core/RedisSessionHandler.php`, `app/Core/CSRFService.php`).
- Migrations: Phinx config in `config/phinx.php` and migration files in `database/migrations/`.

## Security
- Only `public/` is web-accessible; everything else is server-side.
- Auth enforced by middleware and session checks.
- CSRF validation required on all mutating forms; tokens stored in Redis.