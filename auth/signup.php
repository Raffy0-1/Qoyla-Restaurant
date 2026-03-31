<?php
// ============================================================
// QOYLA — SIGNUP PAGE
// File: auth/signup.php
// ============================================================
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Already logged in? redirect
if (isset($_SESSION['user_id'])) { header('Location: /qoyla/dashboard/index.php'); exit; }

$signupError = '';
$formData    = []; // keep form values on error so user doesn't retype

// ---- Handle Signup (POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $formData = $_POST;
    $name     = trim($_POST['name']             ?? '');
    $phone    = trim($_POST['phone']            ?? '');
    $email    = trim($_POST['email']            ?? '');
    $pass     = $_POST['password']              ?? '';
    $confirm  = $_POST['confirm_password']      ?? '';

    // --- Validate ---
    if (empty($name) || empty($phone) || empty($pass)) {
        $signupError = 'Name, phone number and password are required.';
    } elseif (strlen($pass) < 6) {
        $signupError = 'Password must be at least 6 characters.';
    } elseif ($pass !== $confirm) {
        $signupError = 'Passwords do not match.';
    } elseif (!preg_match('/^03[0-9]{9}$/', $phone)) {
        $signupError = 'Enter a valid Pakistani phone number (e.g. 03001234567).';
    } else {
        // Check phone not already registered
        $check = $pdo->prepare("SELECT id FROM customers WHERE phone = ?");
        $check->execute([$phone]);
        if ($check->fetch()) {
            $signupError = 'This phone number is already registered. Please log in.';
        } else {
            // All good — insert customer
            $hashedPass = password_hash($pass, PASSWORD_BCRYPT);

            // Auto-assign next sr_no
            $maxSr = $pdo->query("SELECT COALESCE(MAX(sr_no), 0) + 1 FROM customers")->fetchColumn();

            $stmt = $pdo->prepare("
                INSERT INTO customers (sr_no, name, phone, email, password, total_points)
                VALUES (?, ?, ?, ?, ?, 0)
            ");
            $stmt->execute([$maxSr, $name, $phone, $email ?: null, $hashedPass]);

            // Redirect to login with success message
            header('Location: /qoyla/auth/login.php?registered=1');
            exit;
        }
    }
}

$pageTitle = 'Sign Up | Qoyla Loyalty Club';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="/qoyla/assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="auth-page">
  <div class="auth-card" style="max-width:500px;" data-aos="zoom-in">

    <div class="auth-logo">QOYLA</div>
    <p class="auth-sub">Join the loyalty club — earn points on every visit</p>

    <!-- Perks strip -->
    <div style="background:var(--flame-glow);border-radius:var(--radius-sm);padding:0.85rem 1rem;margin-bottom:1.75rem;display:flex;gap:1.5rem;flex-wrap:wrap;justify-content:center;">
      <span style="font-size:0.78rem;font-weight:700;color:var(--flame-orange);">
        <i class="fas fa-star" style="margin-right:4px;"></i>Earn Points
      </span>
      <span style="font-size:0.78rem;font-weight:700;color:var(--flame-orange);">
        <i class="fas fa-tag" style="margin-right:4px;"></i>Exclusive Deals
      </span>
      <span style="font-size:0.78rem;font-weight:700;color:var(--flame-orange);">
        <i class="fas fa-gift" style="margin-right:4px;"></i>Free Rewards
      </span>
    </div>

    <!-- Error message -->
    <?php if ($signupError): ?>
      <div class="flash flash-error">
        <i class="fas fa-exclamation-circle"></i> <?= e($signupError) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="/qoyla/auth/signup.php" data-loading>

      <div class="form-group">
        <label class="form-label">Full Name *</label>
        <input type="text" name="name" class="form-input"
               placeholder="e.g. Ali Hassan" required
               value="<?= e($formData['name'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label">
          Phone Number *
          <span style="font-size:0.72rem;color:var(--text-muted);font-weight:400;">(your login ID)</span>
        </label>
        <input type="tel" name="phone" class="form-input"
               placeholder="03001234567" required
               value="<?= e($formData['phone'] ?? '') ?>"
               autocomplete="username">
        <div class="form-hint">10 digits starting with 03. No dashes. Must be unique.</div>
      </div>

      <div class="form-group">
        <label class="form-label">
          Email
          <span style="font-size:0.72rem;color:var(--text-muted);font-weight:400;">(optional)</span>
        </label>
        <input type="email" name="email" class="form-input"
               placeholder="your@email.com"
               value="<?= e($formData['email'] ?? '') ?>">
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group">
          <label class="form-label">Password *</label>
          <div style="position:relative;">
            <input type="password" name="password" id="passField"
                   class="form-input" placeholder="Min 6 characters"
                   required style="padding-right:3rem;">
            <button type="button"
                    onclick="togglePassword('passField','eyeIcon1')"
                    style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;">
              <i class="fas fa-eye" id="eyeIcon1"></i>
            </button>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm *</label>
          <input type="password" name="confirm_password" class="form-input"
                 placeholder="Repeat password" required>
        </div>
      </div>

      <div style="margin-bottom:1.5rem;">
        <label style="display:flex;align-items:flex-start;gap:0.6rem;cursor:pointer;font-size:0.85rem;color:var(--text-muted);line-height:1.5;">
          <input type="checkbox" name="terms" required
                 style="accent-color:var(--flame-orange);width:15px;height:15px;margin-top:3px;flex-shrink:0;">
          I agree to the terms. I understand my visits will be tracked for loyalty points.
        </label>
      </div>

      <button type="submit" class="btn-qoyla"
              style="width:100%;justify-content:center;">
        Create Account <i class="fas fa-star"></i>
      </button>
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
</form>

    <div class="auth-divider"><span>already a member?</span></div>

    <p style="text-align:center;font-size:0.9rem;color:var(--text-muted);">
      <a href="/qoyla/auth/login.php"
         style="color:var(--flame-orange);font-weight:700;">
        Log in to your account
      </a>
    </p>

    <div style="text-align:center;margin-top:1.25rem;">
      <a href="/qoyla/index.php" style="font-size:0.82rem;color:var(--text-muted);">
        <i class="fas fa-arrow-left" style="margin-right:5px;"></i> Back to Restaurant
      </a>
    </div>

  </div>
</div>

<script src="/qoyla/assets/js/main.js"></script>
</body>
</html>
