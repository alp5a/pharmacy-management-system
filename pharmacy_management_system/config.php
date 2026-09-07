<?php
/**
 * config.php
 * ----------
 * Database connection settings. Edit DB_HOST / DB_NAME / DB_USER / DB_PASS
 * to match your MySQL server (defaults below match a typical XAMPP/WAMP setup).
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'pharmacy_db');
define('DB_USER', 'root');
define('DB_PASS', '');           // set your MySQL root password here if you have one

define('APP_NAME', 'MediCare Pharmacy');
define('CURRENCY', 'Tk. ');      // change to '$' , '৳' , etc. as you like

date_default_timezone_set('Asia/Dhaka'); // change to your timezone

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . htmlspecialchars($e->getMessage()) .
        "<br><br>Make sure you imported sql/schema.sql and that config.php has the right credentials.");
}

session_start();
