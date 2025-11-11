# Cron Jobs

This directory contains scripts that are intended to be run automatically
on a schedule (e.g., via `cron`).

## Turn Processor

The `process_turn.php` script is the "heartbeat" of the game. It loops
through all users and applies turn-based income (credits, interest, citizens).

---

### Manual Execution

You can run the script manually from your project's root directory:


php /usr/local/var/www/starlight_v2/cron/process_turn.php

or simply

php cron/process_turn.php

You should see output like:

Starting turn processing... [2025-11-11 10:30:01]
Turn processing complete. 
Processed 5 users in 0.02 seconds. 
Automatic Execution (Cron)
