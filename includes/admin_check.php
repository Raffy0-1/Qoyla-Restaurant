<?php
// ============================================================
// Paste this at the TOP of every admin page
// ============================================================
if (!isset($_SESSION['admin_id'])) {
    header('Location: /qoyla/admin/login.php');
    exit;
}
?>