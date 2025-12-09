#StarlightDominion V2: Modular Space Strategy Engine

StarlightDominion V2 is a complete rewrite of a classic browser-based space strategy game, built on a robust, scalable Model-View-Controller-Service (MVC-S) architecture using modern PHP 8.4 standards.

The primary goal of this architecture is strict separation of concerns and transactional integrity.

✨ Core Features

The application is built as a series of atomic, vertically-sliced features:

Authentication & Security: Secure user registration, login, CSRF protection, and gated settings for password/email updates [cite: mungus451/starlight_v2/starlight_v2-master/app/Middleware/AuthMiddleware.php, mungus451/starlight_v2/starlight_v2-master/app/Core/CSRFService.php].

Dynamic Economy: Dashboard, Bank (deposit/withdraw/transfer), Training, and Structures, all managed with complex, configurable game balance formulas [cite: mungus451/starlight_v2/starlight_v2-master/config/game_balance.php].

Armory & Loadouts: Manufacture equipment and assign loadouts to units (Soldiers, Guards, Workers, Spies) that dynamically affect power and income calculations [cite: mungus451/starlight_v2/starlight_v2-master/app/Models/Services/ArmoryService.php].

PvP Combat: All-in attack and espionage operations with detailed reporting, integrated alliance tax, and war logging functionality [cite: mungus451/starlight_v2/starlight_v2-master/app/Models/Services/AttackService.php, mungus451/starlight_v2/starlight_v2-master/app/Models/Services/SpyService.php].

Alliance System: Full lifecycle management (create, join, leave, roles, diplomacy, war), enforced by a granular Role-Based Access Control (RBAC) layer [cite: mungus451/starlight_v2/starlight_v2-master/app/Models/Entities/AllianceRole.php, mungus451/starlight_v2/starlight_v2-master/app/Models/Services/AlliancePolicyService.php].

Game Loop (Cron): A standalone script for processing turn-based income, citizen growth, and alliance interest [cite: mungus451/starlight_v2/starlight_v2-master/cron/process_turn.php].

```
🚀 Tech Stack

Component

Technology

Role

Backend

PHP 8.4 (or newer)

Server-side logic and processing.

Database

MariaDB (or MySQL)

Highly normalized and transactional data persistence.

Routing

nikic/fast-route

High-performance URI dispatching.

Configuration

vlucas/phpdotenv

Environment variable management.
```


🏛️ Architecture: The MVC-S Pattern

The core principle of this project is strict Separation of Concerns. Business logic is never mixed with presentation or database queries.

Project Structure Overview

```
starlight_v2/
├── app/
│   ├── Controllers/    # C: Handles HTTP I/O.
│   ├── Core/           # Database, Session, Config.
│   └── Models/
│       ├── Entities/       # D: Dumb Data Objects (DTOs).
│       ├── Repositories/   # R: Data Access Layer (RAW SQL).
│       └── Services/       # S: Business Logic Layer (The "M" orchestrator).
├── config/             # Game balance and environment variables.
├── cron/               # Game loop scripts.
├── public/             # Web entry point (index.php).
└── views/              # V: HTML templates.
```

```
#Application Flow: From Request to Transaction

A typical feature flow follows this sequence:

Layer

File/Component

Responsibility

Router

public/index.php

Maps URI to Controller::method. Enforces AuthMiddleware.

Controller
```

App\Controllers\...

1. Receives Request, 2. Calls Service, 3. Renders View. Never contains business logic or SQL [cite: mungus451/starlight_v2/starlight_v2-master/app/Controllers/BaseController.php].

Service

App\Models\Services\...

1. Orchestrates Logic. Validates input, begins a transaction, orchestrates multiple Repository calls, and applies game balance rules (from config/).

Repository

App\Models\Repositories\...

1. Executes SQL. Contains raw parameterized queries. Fetches data and returns Entities to the Service. Never contains game logic or knows about HTTP/sessions [cite: mungus451/starlight_v2/starlight_v2-master/app/Models/Repositories/UserRepository.php].

Database

App\Core\Database.php

Provides a single, transactional PDO connection instance.

View

views/...

1. Presents Data. Consumes data passed by the Controller and renders HTML (including CSRF tokens from BaseController) [cite: mungus451/starlight_v2/starlight_v2-master/views/layouts/main.php].

⚙️ Development Guide: Adding a New Feature

When implementing a new "vertical slice" feature (e.g., "Item Repair"), follow this explicit structure:

Define the Data (Entity): Create app/Models/Entities/ItemRepair.php to represent the database row structure.

Define the Database Access (Repository): Create app/Models/Repositories/ItemRepairRepository.php to handle all SELECT, INSERT, and UPDATE queries for this data, returning ItemRepair entities.

Define the Business Logic (Service): Create app/Models/Services/ItemRepairService.php. This class handles validation (Do they have resources?), calls PowerCalculatorService (Is the repair effective?), starts a transaction, and calls the necessary repositories (ItemRepairRepository, ResourceRepository).

Define the HTTP Interface (Controller): Create app/Controllers/ItemRepairController.php. This handles GET to display the view, POST to process the form, validates the CSRF token, calls ItemRepairService, and handles the final redirect/flash message.

Define the Frontend (View): Create views/item_repair/show.php for the UI.

Define the Route (Router): Update public/index.php to map the new URI (/repair) to ItemRepairController::show.

🛠️ Installation & Setup (Ubuntu/MariaDB/Redis)

This guide assumes a Ubuntu server environment running Apache2, PHP 8.4, MariaDB, and Redis.

1. Prerequisites (Ubuntu)

Ensure the necessary components are installed and running:

# Install PHP and extensions required by Composer (e.g., mbstring, xml, redis)
sudo apt update
sudo apt install php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml php8.4-zip php8.4-curl composer

# Install Database & Cache Servers
sudo apt install mariadb-server redis-server

# Start the services (usually automatic after install)
sudo systemctl start mariadb
sudo systemctl start redis-server
sudo systemctl start apache2


2. Database Setup

Log in to your MariaDB instance and create the production database and user:

sudo mysql

# Replace 'sd_admin' and 'starlight' with your credentials
CREATE DATABASE starlightDB;
CREATE USER 'sd_admin'@'localhost' IDENTIFIED BY 'starlight';
GRANT ALL PRIVILEGES ON starlightDB.* TO 'sd_admin'@'localhost';
FLUSH PRIVILEGES;
EXIT;


3. Application Setup

Place Files: Place the project files in the web root, e.g., `/var/www/html/starlight_v2`.

Install Dependencies:
This installs core libraries including `predis/predis` (for Redis) and `robmorgan/phinx` (for migrations).

cd /var/www/html/starlight_v2
composer install


Configure .env: Copy the `.env.example` file to `.env` and set your database and Redis credentials.

cp .env.example .env
nano .env
# Set DB_HOST, DB_NAME, DB_USER, DB_PASS
# Set REDIS_HOST, REDIS_PORT (Defaults to 127.0.0.1:6379)


Run Database Migrations:
Use Phinx to build the database schema and apply all recent changes.

vendor/bin/phinx migrate --configuration=config/phinx.php


Set Directory Permissions: Crucially, grant the Apache user (`www-data`) write access to storage and log directories:

# Grant ownership of storage and logs to the web server user
sudo chown -R www-data:www-data storage/ logs/ public/uploads/
# Set correct directory permissions (rwxr-xr-x)
sudo chmod -R 755 storage/ logs/ public/uploads/


4. Running the Game Loop (Cron)

Set up a cron job to run the turn processor, which handles all passive income and interest:

crontab -e


Add this line to run every 10 minutes:

*/10 * * * * cd /var/www/html/starlight_v2 && /usr/bin/php8.4 cron/process_turn.php >> logs/cron.log 2>&1
