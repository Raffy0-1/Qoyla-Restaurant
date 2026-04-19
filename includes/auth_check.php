<?php
// ============================================================
// Paste this at the TOP of any page only logged-in
// CUSTOMERS should see (dashboard pages)
// ============================================================
if (!isset($_SESSION['user_id'])) {
    setFlash('error', 'Please log in to access your dashboard.');
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}
?>