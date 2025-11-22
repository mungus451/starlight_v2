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
# PHP 8.3

# MariaDB (or MySQL)

# Composer for package management

# vlucas/phpdotenv: For managing environment variables.

# nikic/fast-route: For clean, high-performance routing.

📁 Project Structure
### This application follows a strict Model-View-Controller (MVC) pattern.

`/usr/local/var/www/starlight_v2/` <br>
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
`├── sql/                # All database migration scripts (in order).` <br>
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

# Install PHP 8.3
`brew install php@8.3`

# Install MariaDB (or MySQL)
`brew install mariadb`
# Install Composer
`brew install composer`

# Start the MariaDB service
`brew services start mariadb`
## 2. Database Setup
Log in to MariaDB/MySQL:

`mysql -u root`
<h3> Create the new database and user (use the credentials you provided): </h6>

`CREATE DATABASE starlightDB;`<br>
`CREATE USER 'sd_admin'@'localhost' IDENTIFIED BY 'starlight';`<br>
`GRANT ALL PRIVILEGES ON starlightDB.* TO 'sd_admin'@'localhost';`<br>
`FLUSH PRIVILEGES;`<br>
`EXIT;`<br>

<h4>Use the database.sql file provided to match the schema.</h4>

3. Application Setup
`Place the project files in /usr/local/var/www/starlight_v2.`

Install Dependencies:

`cd /usr/local/var/www/starlight_v2
composer install
Create your .env file: Copy the example file and edit it if necessary (the defaults are already set to your credentials).`



🏃 Running the Application
## 1. Local Web Server
Use the PHP built-in server. From the project root (/usr/local/var/www/starlight_v2), run:

`php -S localhost:8000 -t public
localhost:8000: The host and port.`

-t public: Crucial. This sets the web root to the /public directory, securing the rest of the application.

You can now access the game at: http://localhost:8000

## 2. Game Loop (Cron Job)
The game's economy (income, interest) is run by a cron job.

To run it manually:

`php cron/process_turn.php`
To set it up to run every 5 minutes (on macOS):

Grant Permissions: cron needs Full Disk Access to run.

`Go to System Settings > Privacy & Security > Full Disk Access.`

`Click + and use Cmd+Shift+G to go to /usr/local/bin. Add php.`

`Click + and use Cmd+Shift+G to go to /bin. Add zsh (or sh).`

`Click + and use Cmd+Shift+G to go to /usr/sbin. Add cron.`

`Make sure all three (php, zsh/sh, cron) are toggled ON.`

Edit your crontab:

`crontab -e`
Add this line: (Use the path from which php)

`*/5 * * * * cd /usr/local/var/www/starlight_v2 && /usr/local/bin/php cron/process_turn.php >> /usr/local/var/www/starlight_v2/logs/cron.log 2>&1`
Check the log: You can watch the cron job run:

`tail -f logs/cron.log`