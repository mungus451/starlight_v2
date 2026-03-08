# StarlightDominion V2: Modular Space Strategy Engine

StarlightDominion V2 is a complete rewrite of a classic browser-based space strategy game, built on a robust, scalable Model-View-Controller-Service (MVC-S) architecture using modern PHP 8.4+ standards.

The primary goal of this architecture is strict separation of concerns and transactional integrity.

## 📚 Documentation

- **Architecture Guide**: [docs/architecture/index.md](docs/architecture/index.md)
- **Getting Started**: [docs/getting-started/index.md](docs/getting-started/index.md)
- **Contributing**: [docs/CONTRIBUTING.md](docs/CONTRIBUTING.md)
- **Docker Guide**: [docs/DOCKER.md](docs/DOCKER.md)

## ✨ Core Features

The application is built as a series of atomic, vertically-sliced features:

Authentication & Security: Secure user registration, login, CSRF protection, and gated settings for password/email updates [cite: mungus451/starlight_v2/starlight_v2-master/app/Middleware/AuthMiddleware.php, mungus451/starlight_v2/starlight_v2-master/app/Core/CSRFService.php].

Dynamic Economy: Dashboard, Bank (deposit/withdraw/transfer), Training, and Structures, all managed with complex, configurable game balance formulas [cite: mungus451/starlight_v2/starlight_v2-master/config/game_balance.php].

Armory & Loadouts: Manufacture equipment and assign loadouts to units (Soldiers, Guards, Workers, Spies) that dynamically affect power and income calculations [cite: mungus451/starlight_v2/starlight_v2-master/app/Models/Services/ArmoryService.php].

PvP Combat: All-in attack and espionage operations with detailed reporting, integrated alliance tax, and war logging functionality [cite: mungus451/starlight_v2/starlight_v2-master/app/Models/Services/AttackService.php, mungus451/starlight_v2/starlight_v2-master/app/Models/Services/SpyService.php].

Alliance System: Full lifecycle management (create, join, leave, roles, diplomacy, war), enforced by a granular Role-Based Access Control (RBAC) layer [cite: mungus451/starlight_v2/starlight_v2-master/app/Models/Entities/AllianceRole.php, mungus451/starlight_v2/starlight_v2-master/app/Models/Services/AlliancePolicyService.php].

Game Loop (Cron): A standalone script for processing turn-based income, citizen growth, and alliance interest [cite: mungus451/starlight_v2/starlight_v2-master/cron/process_turn.php].

```
🚀 Tech Stack
- **PHP 8.4+**: Server-side logic and processing
- **MariaDB (or MySQL)**: Highly normalized and transactional data persistence
- **Composer**: Package management
- **vlucas/phpdotenv**: Environment variable management
- **nikic/fast-route**: High-performance routing

📁 Project Structure
### This application follows a strict Model-View-Controller (MVC) pattern.

`starlight_v2/` <br>
`├── app/` <br>
`│   ├── Controllers/    # (The "C") Handles HTTP requests.` <br>
`│   ├── Core/           # Core bootstrap (Database, Session, Config, CSRF).` <br>
`│   ├── Middleware/     # Protects routes (e.g., AuthMiddleware).` <br>
`│   └── Models/` <br>
`│       ├── Entities/       # "Dumb" data objects (User, Alliance, etc.)` <br>
`│       ├── Repositories/   # (The "M") All SQL queries live here.` <br>
`│       └── Services/       # (The "M") All business logic lives here.` <br>
`├── config/             # Game balance and app settings.` <br>
`├── cron/               # Standalone scripts for the game loop.` <br>
`├── logs/               # Error and cron logs.` <br>
`├── public/             # The *only* web-accessible directory.` <br>
`│   └── index.php       # (The "Front Controller") All requests come here.` <br>
`├── database/           # Phinx migrations and seeds.` <br>
`├── vendor/             # Composer packages.` <br>
`└── views/              # (The "V") All "dumb" HTML/PHP templates.` <br>
`    ├── alliance/` <br>
`    ├── auth/` <br>
`    ├── bank/` <br>
`    ├── battle/` <br>
`    ├── dashboard/` <br>
`    ├── layouts/        # Main layout (header/footer).` <br>
`    ├── level_up/` <br>
`    ├── settings/` <br>
`    ├── spy/` <br>
`    ├── structures/` <br>
`    └── training/` <br>

⚙️ Installation & Setup (macOS / Homebrew)

1. Prerequisites
Make sure you have Homebrew installed.

# Install PHP 8.4
`brew install php@8.4`

# Install MariaDB (or MySQL)
`brew install mariadb`
# Install Composer
`brew install composer`

# Start the MariaDB service
`brew services start mariadb`
## 2. Database Setup

### Using Docker (Recommended)
The easiest way to run Starlight V2 is using Docker:

```bash
# Clone the repository
git clone <repository-url>
cd starlight_v2

# Copy environment configuration
cp .env.example .env

# Start containers
docker compose up -d

# Run database migrations
docker compose exec app composer phinx migrate

# Check migration status
docker compose exec app composer phinx status
```

The application will be available at http://localhost:8080

### Manual Setup (Local MySQL/MariaDB)
Log in to MariaDB/MySQL:

```bash
mysql -u root
```

Create the database and user:

```sql
CREATE DATABASE starlightDB;
CREATE USER 'sd_admin'@'localhost' IDENTIFIED BY 'starlight';
GRANT ALL PRIVILEGES ON starlightDB.* TO 'sd_admin'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Run database migrations:

```bash
cd /path/to/starlight_v2
composer install
php vendor/bin/phinx migrate --configuration=config/phinx.php
```

3. Application Setup

Place the project files in `/path/to/starlight_v2`.

Install Dependencies:

```bash
cd /path/to/starlight_v2
composer install
```

Create your `.env` file:

```bash
cp .env.example .env
# Edit .env if necessary (defaults match the credentials above)
```



### Ubuntu/Apache Setup (Production)

For Ubuntu server environments running Apache2 and PHP 8.4:

#### Prerequisites

# Install PHP and extensions required by Composer (e.g., mbstring, xml, redis)
sudo apt update
sudo apt install php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml php8.4-zip php8.4-curl composer

# Install Database & Cache Servers
sudo apt install mariadb-server redis-server

# Start services
sudo systemctl start mariadb
sudo systemctl start redis-server
sudo systemctl start apache2
```

#### Directory Permissions

Grant the Apache user write access to storage directories:

```bash
# Grant ownership to web server user
sudo chown -R www-data:www-data /path/to/starlight_v2/storage
sudo chown -R www-data:www-data /path/to/starlight_v2/public/uploads

# Set correct permissions
sudo chmod -R 755 /path/to/starlight_v2/storage
sudo chmod -R 755 /path/to/starlight_v2/public/uploads
```

## 🏃 Running the Application

### 1. Local Web Server

Use the PHP built-in server from the project root:

```bash
php -S localhost:8000 -t public
```

- `localhost:8000`: The host and port
- `-t public`: Sets the web root to `/public` directory (crucial for security)

Access the game at: http://localhost:8000

### 2. Game Loop (Cron Job)

The game's economy (income, interest) is run by a cron job.

#### Manual Execution

```bash
php cron/process_turn.php
```
Configure .env: Copy the `.env.example` file to `.env` and set your database and Redis credentials.

cp .env.example .env
nano .env
#### Set DB_HOST, DB_NAME, DB_USER, DB_PASS
#### Set REDIS_HOST, REDIS_PORT (Defaults to 127.0.0.1:6379)


Run Database Migrations:
Use Phinx to build the database schema and apply all recent changes.

vendor/bin/phinx migrate --configuration=config/phinx.php


Set Directory Permissions: Crucially, grant the Apache user (`www-data`) write access to storage and log directories:

#### Grant ownership of storage and logs to the web server user
sudo chown -R www-data:www-data storage/ logs/ public/uploads/
#### Set correct directory permissions (rwxr-xr-x)
sudo chmod -R 755 storage/ logs/ public/uploads/

#### Automated Setup (macOS)

Grant Full Disk Access permissions (System Settings > Privacy & Security > Full Disk Access):
- Add `/usr/local/bin/php` (or your PHP path from `which php`)
- Add `/bin/zsh` (or `/bin/sh`)
- Add `/usr/sbin/cron`

Edit your crontab:

```bash
crontab -e
```

Add this line to run every 10 minutes:

```cron
*/10 * * * * cd /path/to/starlight_v2 && /usr/local/bin/php cron/process_turn.php >> /path/to/starlight_v2/logs/cron.log 2>&1
```

#### Ubuntu/Linux Setup

```bash
crontab -e
```

Add this line:

```cron
*/10 * * * * cd /path/to/starlight_v2 && /usr/bin/php8.4 cron/process_turn.php >> /path/to/starlight_v2/logs/cron.log 2>&1
```

#### Monitor Logs

```bash
tail -f logs/cron.log
```
