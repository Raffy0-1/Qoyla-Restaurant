<?php
// ============================================================
// QOYLA — LOGIN PAGE
// File: auth/login.php
// ============================================================
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// If already logged in as customer, go to dashboard
if (isset($_SESSION['user_id']))  { header('Location: ' . BASE_URL . 'dashboard/index.php'); exit; }
// If already logged in as admin on THIS tab, go to admin panel
// (But do NOT redirect here — let the form POST decide based on credentials)

$loginError = '';

// ---- Handle Login (POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $loginError = 'Please enter your phone number and password.';
    } else {

        // 1. Check if it's an ADMIN login first
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
        $stmt->execute([$identifier]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            // Admin login success
            session_regenerate_id(true); // security: regenerate session ID
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_role'] = $admin['role'];
            header('Location: ' . BASE_URL . 'admin/index.php');
            exit;
        }

        // 2. Check CUSTOMER login (by phone number)
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE phone = ? LIMIT 1");
        $stmt->execute([$identifier]);
        $customer = $stmt->fetch();

        if ($customer && password_verify($password, $customer['password'])) {
            // Customer login success — clear any lingering admin session
            session_regenerate_id(true);
            unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_role']);
            $_SESSION['user_id']   = $customer['id'];
            $_SESSION['user_name'] = $customer['name'];
            $_SESSION['user_sr']   = $customer['sr_no'];

            // Handle "remember me" — store a cookie for 30 days
            if (!empty($_POST['remember'])) {
                setcookie('qoyla_remember', $customer['id'], time() + (30 * 24 * 60 * 60), '/');
            }

            header('Location: ' . BASE_URL . 'dashboard/index.php');
            exit;
        }

        // Both failed
        $loginError = 'Invalid phone number or password. Please try again.';
    }
}

// ---- Show Login Page ----
$pageTitle = 'Login | Qoyla Restaurant';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="auth-page">
  <div class="auth-card" data-aos="zoom-in">

    <div class="auth-logo">QOYLA</div>
    <p class="auth-sub">Welcome back — log in to your loyalty account</p>

    <!-- Error message -->
    <?php if ($loginError): ?>
      <div class="flash flash-error">
        <i class="fas fa-exclamation-circle"></i> <?= e($loginError) ?>
      </div>
    <?php endif; ?>

    <!-- Success message after signup redirect -->
    <?php if (isset($_GET['registered'])): ?>
      <div class="flash flash-success">
        <i class="fas fa-check-circle"></i> Account created! You can now log in.
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>auth/login.php" data-loading>

      <div class="form-group">
        <label class="form-label">Phone Number</label>
        <input type="text" name="identifier" class="form-input"
               placeholder="03XX XXXXXXX"
               value="<?= e($_POST['identifier'] ?? '') ?>"
               required autocomplete="username">
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <div style="position:relative;">
          <input type="password" name="password" id="passwordField"
                 class="form-input" placeholder="••••••••"
                 required autocomplete="current-password"
                 style="padding-right:3rem;">
          <button type="button"
                  onclick="togglePassword('passwordField','eyeIcon')"
                  style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;padding:0;">
            <i class="fas fa-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>

      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;font-size:0.88rem;color:var(--text-muted);">
          <input type="checkbox" name="remember"
                 style="accent-color:var(--flame-orange);width:15px;height:15px;">
          Remember me for 30 days
        </label>
        <a href="#" class="link-disabled" title="Password reset is not yet available">
          <i class="fas fa-lock"></i> Forgot password?
        </a>
      </div>

      <button type="submit" class="btn-qoyla"
              style="width:100%;justify-content:center;font-size:0.92rem;">
        Log In <i class="fas fa-arrow-right"></i>
      </button>
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
</form>

    <div class="auth-divider"><span>or</span></div>

    <p style="text-align:center;font-size:0.9rem;color:var(--text-muted);">
      New to Qoyla?
      <a href="<?= BASE_URL ?>auth/signup.php"
         style="color:var(--flame-orange);font-weight:700;margin-left:4px;">
        Create a free account
      </a>
    </p>

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
