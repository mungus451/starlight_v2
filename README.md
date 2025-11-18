#StarlightDominion V2: Modular Space Strategy Engine

StarlightDominion V2 is a complete rewrite of a classic browser-based space strategy game, built on a robust, scalable Model-View-Controller-Service (MVC-S) architecture using modern PHP 8.3 standards.

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

PHP 8.3 (or newer)

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

🛠️ Installation & Setup (Ubuntu/MariaDB)

This guide assumes a Ubuntu server environment running Apache2 and PHP 8.3.

1. Prerequisites (Ubuntu)

Ensure the necessary components are installed and running:

# Install PHP and extensions required by Composer (e.g., mbstring)
sudo apt update
sudo apt install php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-zip php8.3-curl composer

# Install MariaDB
sudo apt install mariadb-server

# Start the services (usually automatic after install)
sudo systemctl start mariadb
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


Next, import the schema and seed data using your local SQL files.

3. Application Setup

Place Files: Place the project files in the web root, e.g., /usr/local/var/www/starlight_v2.

Install Dependencies:

cd /usr/local/var/www/starlight_v2
composer install


Configure .env: Copy the .env.example file to .env and set your database credentials.

Set Directory Permissions: Crucially, grant the Apache user (www-data) write access to the uploaded files directory:

# Grant ownership of the storage folders to the web server user (www-data)
sudo chown -R www-data:www-data /usr/local/var/www/starlight_v2/storage
sudo chown -R www-data:www-data /usr/local/var/www/starlight_v2/public/uploads
# Set correct directory permissions (rwxr-xr-x)
sudo chmod -R 755 /usr/local/var/www/starlight_v2/storage
sudo chmod -R 755 /usr/local/var/www/starlight_v2/public/uploads


4. Running the Game Loop (Cron)

Set up a cron job to run the turn processor, which handles all passive income and interest:

crontab -e


Add this line to run every 5 minutes:

*/5 * * * * cd /usr/local/var/www/starlight_v2 && /usr/bin/php8.3 cron/process_turn.php >> /usr/local/var/www/starlight_v2/logs/cron.log 2>&1
