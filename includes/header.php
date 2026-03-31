<?php
// Variables to set BEFORE including this file:
// $pageTitle  — string shown in browser tab
// $activePage — 'home' | 'menu' | 'gallery' | 'about' | 'contact'
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Qoyla Restaurant') ?></title>
  <meta name="description" content="Qoyla Restaurant - Experience authentic Pakistani cuisine with our exclusive loyalty program and deals.">
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="/qoyla/assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="qoyla-nav">
  <div class="nav-inner">
    <a href="/qoyla/index.php" class="nav-brand">
      QOYLA<span>Restaurant · Multan</span>
    </a>
    <div class="nav-links">
      <a href="/qoyla/index.php"
         class="<?= ($activePage==='home')    ? 'active' : '' ?>">Home</a>
      <a href="/qoyla/menu.php"
         class="<?= ($activePage==='menu')    ? 'active' : '' ?>">Menu</a>
      <a href="/qoyla/gallery.php"
         class="<?= ($activePage==='gallery') ? 'active' : '' ?>">Gallery</a>
      <a href="/qoyla/about.php"
         class="<?= ($activePage==='about')   ? 'active' : '' ?>">About</a>
      <a href="/qoyla/contact.php"
         class="<?= ($activePage==='contact') ? 'active' : '' ?>">Contact</a>

      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="/qoyla/dashboard/index.php" class="nav-btn-login">
          My Dashboard
        </a>
      <?php else: ?>
        <a href="/qoyla/auth/login.php" class="nav-btn-login">Login</a>
      <?php endif; ?>
    </div>
    <button class="nav-hamburger" id="navHamburger">
      <span></span><span></span><span></span>
    </button>
  </div>
  <div class="nav-mobile" id="navMobile">
    <a href="/qoyla/index.php" class="<?= ($activePage==='home') ? 'active' : '' ?>">Home</a>
    <a href="/qoyla/menu.php" class="<?= ($activePage==='menu') ? 'active' : '' ?>">Menu</a>
    <a href="/qoyla/gallery.php" class="<?= ($activePage==='gallery') ? 'active' : '' ?>">Gallery</a>
    <a href="/qoyla/about.php" class="<?= ($activePage==='about') ? 'active' : '' ?>">About</a>
    <a href="/qoyla/contact.php" class="<?= ($activePage==='contact') ? 'active' : '' ?>">Contact</a>
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="/qoyla/dashboard/index.php">My Dashboard</a>
    <?php else: ?>
      <a href="/qoyla/auth/login.php">Login</a>
    <?php endif; ?>
  </div>
</nav>