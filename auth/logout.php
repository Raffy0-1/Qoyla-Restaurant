<?php
// ============================================================
// QOYLA — LOGOUT
// File: auth/logout.php
// ============================================================
session_start();

// Destroy everything
$_SESSION = [];
session_destroy();

// Clear remember me cookie
if (isset($_COOKIE['qoyla_remember'])) {
    setcookie('qoyla_remember', '', time() - 3600, '/');
}

// Redirect to homepage
header('Location: ' . BASE_URL . 'index.php');
exit;
