# StarlightDominion V2
StarlightDominion V2 is a complete rewrite of a classic browser-based space strategy game. This project re-architects the original application into a modern, scalable, and maintainable Model-View-Controller (MVC) pattern built on PHP 8.3 and MariaDB.

✨ Features
The application is built as a series of "vertical slices," with the following features fully implemented:

Authentication: Secure user registration and login.

Dynamic Dashboard: A central hub showing a player's real-time resources, stats, and structures.

Bank: Securely deposit, withdraw, and transfer credits to other players.

Training: Train untrained citizens into specialized units (workers, soldiers, spies, etc.) using game-balanced costs.

Structures: Upgrade your dominion's structures (Fortification, Economy, etc.) using dynamically calculated costs.

Espionage: Launch "all-in" spy operations against other players, costing attack turns and resources.

Spy Reports: A detailed inbox for viewing the results and intel gathered from spy operations.

Battle (PvP): A full-featured "all-in" attack system.

Leaderboard: A paginated leaderboard of all players, ranked by net worth, is built into the battle page for target selection.

Dynamic Combat: Battle outcomes are calculated based on unit counts, stats (strength, constitution), and structure levels (offense, defense).

Spoils of War: Victors plunder credits and steal net worth.

Battle Reports: A detailed inbox for viewing all battle outcomes.

Level Up: Spend level-up points to increase your character's five core stats.

Settings: A multi-part page to update your profile, change your email, update your password, and set security questions (all sensitive actions are gated by a password check).

Alliances (Full System):

View a public, paginated list of all alliances.

Create a new alliance (costs credits).

View a public alliance profile and member roster.

Apply to, cancel applications, and leave an alliance.

Dynamic Role-Based Permissions: A complete alliance administration system where Leaders can:

Edit the alliance profile.

Accept or reject applications.

Kick members.

Promote/demote members.

Create, edit, and delete custom roles with granular permissions (e.g., can_kick_members, can_manage_bank).

Game Loop (Cron): A standalone "heartbeat" script that processes turn-based income (credits, interest, citizens) for all players.

🚀 Tech Stack
PHP 8.3

MariaDB (or MySQL)

Composer for package management

vlucas/phpdotenv: For managing environment variables.

nikic/fast-route: For clean, high-performance routing.

📁 Project Structure
This application follows a strict Model-View-Controller (MVC) pattern.

`/usr/local/var/www/starlight_v2/
├── app/
│   ├── Controllers/    # (The "C") Handles HTTP requests.
│   ├── Core/           # Core bootstrap (Database, Session, Config, CSRF).
│   ├── Middleware/     # Protects routes (e.g., AuthMiddleware).
│   └── Models/
│       ├── Entities/       # "Dumb" data objects (User, Alliance, etc.)
│       ├── Repositories/   # (The "M") All SQL queries live here.
│       └── Services/       # (The "M") All business logic lives here.
├── config/             # Game balance and app settings.
├── cron/               # Standalone scripts for the game loop.
├── logs/               # Error and cron logs.
├── public/             # The *only* web-accessible directory.
│   └── index.php       # (The "Front Controller") All requests come here.
├── sql/                # All database migration scripts (in order).
├── vendor/             # Composer packages.
└── views/              # (The "V") All "dumb" HTML/PHP templates.
    ├── alliance/
    ├── auth/
    ├── bank/
    ├── battle/
    ├── dashboard/
    ├── layouts/        # Main layout (header/footer).
    ├── level_up/
    ├── settings/
    ├── spy/
    ├── structures/
    └── training/`
⚙️ Installation & Setup (macOS / Homebrew)
1. Prerequisites
Make sure you have Homebrew installed.

Bash

# Install PHP 8.3
brew install php@8.3

# Install MariaDB (or MySQL)
brew install mariadb

# Install Composer
brew install composer

# Start the MariaDB service
brew services start mariadb
2. Database Setup
Log in to MariaDB/MySQL:

Bash

mysql -u root
Create the new database and user (use the credentials you provided):

SQL

CREATE DATABASE starlightDB;
CREATE USER 'sd_admin'@'localhost' IDENTIFIED BY 'starlight';
GRANT ALL PRIVILEGES ON starlightDB.* TO 'sd_admin'@'localhost';
FLUSH PRIVILEGES;
EXIT;
3. Application Setup
Place the project files in /usr/local/var/www/starlight_v2.

Install Composer dependencies:

Bash

cd /usr/local/var/www/starlight_v2
composer install
Create your .env file: Copy the example file and edit it if necessary (the defaults are already set to your credentials).

Bash

cp .env.example .env
Run ALL SQL Migrations (in order): Log back into your database and run all the scripts from the /sql directory one-by-one.

Bash

mysql -u sd_admin -p starlightDB < sql/01_create_users_table.sql
mysql -u sd_admin -p starlightDB < sql/02_create_user_resources.sql
mysql -u sd_admin -p starlightDB < sql/03_create_user_stats.sql
mysql -u sd_admin -p starlightDB < sql/04_create_user_structures.sql
mysql -u sd_admin -p starlightDB < sql/05_alter_users_table.sql
mysql -u sd_admin -p starlightDB < sql/06_create_user_security.sql
mysql -u sd_admin -p starlightDB < sql/07_create_spy_reports.sql
mysql -u sd_admin -p starlightDB < sql/08_create_battle_reports.sql
mysql -u sd_admin -p starlightDB < sql/10_create_alliances_table.sql
mysql -u sd_admin -p starlightDB < sql/11_alter_users_table_for_alliances.sql
mysql -u sd_admin -p starlightDB < sql/12_create_alliance_applications.sql
mysql -u sd_admin -p starlightDB < sql/13_create_alliance_roles.sql
mysql -u sd_admin -p starlightDB < sql/14_refactor_users_for_roles.sql
Run the Data Migration: This one-time script is required to create the default alliance roles.

Bash

php migrations/13.1_migrate_roles.php
🏃 Running the Application
1. Local Web Server
Use the PHP built-in server. From the project root (/usr/local/var/www/starlight_v2), run:

Bash

php -S localhost:8000 -t public
localhost:8000: The host and port.

-t public: Crucial. This sets the web root to the /public directory, securing the rest of the application.

You can now access the game at: http://localhost:8000

2. Game Loop (Cron Job)
The game's economy (income, interest) is run by a cron job.

To run it manually:

Bash

php cron/process_turn.php
To set it up to run every 5 minutes (on macOS):

Grant Permissions: cron needs Full Disk Access to run.

Go to System Settings > Privacy & Security > Full Disk Access.

Click + and use Cmd+Shift+G to go to /usr/local/bin. Add php.

Click + and use Cmd+Shift+G to go to /bin. Add zsh (or sh).

Click + and use Cmd+Shift+G to go to /usr/sbin. Add cron.

Make sure all three (php, zsh/sh, cron) are toggled ON.

Edit your crontab:

Bash

crontab -e
Add this line: (Use the path from which php)

Code snippet

*/5 * * * * cd /usr/local/var/www/starlight_v2 && /usr/local/bin/php cron/process_turn.php >> /usr/local/var/www/starlight_v2/logs/cron.log 2>&1
Check the log: You can watch the cron job run:

Bash

tail -f logs/cron.log