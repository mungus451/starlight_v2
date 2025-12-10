<?php
require __DIR__ . '/vendor/autoload.php';

use App\Core\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$db = Database::getInstance();
$db->exec("DROP TABLE IF EXISTS user_active_effects");
$db->exec("DROP TABLE IF EXISTS intel_listings");
echo "Dropped tables.\n";

