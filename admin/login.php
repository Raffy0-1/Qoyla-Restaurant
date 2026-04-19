<?php
// ============================================================
// QOYLA — ADMIN LOGIN
// File: admin/login.php
// ============================================================
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (isset($_SESSION['admin_id'])) { header('Location: ' . BASE_URL . 'admin/index.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_role'] = $admin['role'];
            header('Location: ' . BASE_URL . 'admin/index.php');
            exit;
        }
        $error = 'Invalid username or password.';
    } else {
        $error = 'Please enter username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | Qoyla</title>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="auth-page" style="background:var(--charcoal-black);">
  <div class="auth-card" style="max-width:400px;">
    <div class="auth-logo">QOYLA</div>
    <p class="auth-sub" style="margin-bottom:0.5rem;">Admin Panel Access</p>
    <p style="text-align:center;font-size:0.78rem;color:#EF4444;margin-bottom:1.5rem;font-weight:700;letter-spacing:0.5px;">RESTRICTED — STAFF ONLY</p>

    <?php if ($error): ?>
      <div class="flash flash-error"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-input" required
               value="<?= e($_POST['username'] ?? '') ?>" autocomplete="username">
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-input" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn-qoyla" style="width:100%;justify-content:center;margin-top:0.5rem;">
        Enter Admin Panel <i class="fas fa-lock-open"></i>
      </button>
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
</form>

    <div style="text-align:center;margin-top:1.5rem;">
      <a href="<?= BASE_URL ?>index.php" style="font-size:0.82rem;color:var(--text-muted);">
        <i class="fas fa-arrow-left" style="margin-right:5px;"></i> Back to Restaurant
      </a>
    </div>
  </div>
</div>
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
