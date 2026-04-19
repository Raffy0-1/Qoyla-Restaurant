<?php
// ============================================================
// QOYLA — DATABASE CONNECTION
// This file is included at the top of every PHP page
// ============================================================

// === CHANGE BEFORE GOING LIVE ===
// Replace the values below with your hosting credentials
// and change APP_ENV to 'production'
define('APP_ENV', 'development');

// Determine Base URL based on environment
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    define('BASE_URL', '/qoyla/');
} else {
    define('BASE_URL', '/');
}

$host = 'localhost';
$db   = 'qoyla_db';
$user = 'root';
$pass = '';         // XAMPP default: empty password — do NOT change this for local

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    if (APP_ENV === 'production') {
        die('Database Connection Failed. Please try again later.');
    }
    die('
    <div style="font-family:sans-serif; padding:3rem; text-align:center;">
      <h2 style="color:#DC2626;">❌ Database Connection Failed</h2>
      <p style="color:#666;">' . $e->getMessage() . '</p>
      <p style="color:#666; font-size:0.9rem;">
        Make sure MySQL is running (green) in XAMPP Control Panel.
      </p>
    </div>');
}
?>