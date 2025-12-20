# Installation

## Prerequisites

- **PHP 8.4+**
- **MariaDB or MySQL**
- **Composer**
- **Docker** (recommended)

## Docker Setup (Recommended)

Use Docker for a reproducible environment. Full instructions live in [DOCKER.md](../DOCKER.md); the essentials are below:

1. Copy env: `cp .env.example .env`
2. Start services: `docker compose up -d --build`
3. Install + migrate: `docker compose exec app composer install` and `docker compose exec app php vendor/bin/phinx migrate`
4. Open http://localhost:8080

## Manual Setup

1. **Install dependencies**
   ```bash
   composer install
   ```

2. **Configure environment**
   ```bash
   cp .env.example .env
   # Update DATABASE_HOST, DATABASE_NAME, DATABASE_USER, DATABASE_PASSWORD
   ```

3. **Run migrations**
   ```bash
   php vendor/bin/phinx migrate
   ```

4. **Start development server**
   ```bash
   php -S localhost:8000 -t public
   ```

5. **Access at** [http://localhost:8000](http://localhost:8000)

## Troubleshooting

- **Database connection errors**: Verify `.env` credentials match your database
- **Permission errors**: Ensure `logs/` directory is writable
- **Missing extensions**: Install `php-pdo`, `php-mysql`, `php-redis`

See [Troubleshooting](troubleshooting.md) for more common issues.
