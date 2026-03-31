<?php
// ============================================================
// QOYLA — MENU PAGE
// File: menu.php  (replaces menu.html)
// ============================================================
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle  = 'Menu | Qoyla Restaurant';
$activePage = 'menu';
include 'includes/header.php';

// Fetch ALL available menu items grouped by category
$stmt = $pdo->query("SELECT * FROM menu_items WHERE is_available = 1 ORDER BY category, name");
$allItems = $stmt->fetchAll();

// Group by category for tab display
$byCategory = [];
foreach ($allItems as $item) {
    $byCategory[$item['category']][] = $item;
}

// Category display config
$categories = [
    'all'         => ['label' => '🔥 All',         'filter' => 'all'],
    'meats'       => ['label' => '🥩 Meats',        'filter' => 'meats'],
    'main_course' => ['label' => '🍛 Main Course',  'filter' => 'main_course'],
    'sweets'      => ['label' => '🍯 Sweets',       'filter' => 'sweets'],
    'drinks'      => ['label' => '☕ Drinks',        'filter' => 'drinks'],
    'deals'       => ['label' => '🎁 Deals',        'filter' => 'deals'],
];
?>

<!-- Page Hero -->
<div style="background:var(--charcoal-black);padding:4rem 0 3rem;text-align:center;">
  <div class="container">
    <div class="hero-eyebrow" style="margin:0 auto 1rem;display:inline-flex;" data-aos="fade-down">
      <i class="fas fa-utensils"></i> Over Charcoal, Every Dish
    </div>
    <h1 class="section-title center-line white-title" data-aos="fade-up">Our Menu</h1>
    <p style="color:rgba(255,255,255,0.55);margin-top:1.5rem;font-size:1rem;" data-aos="fade-up" data-aos-delay="100">
      Every dish cooked over real qoyla — the way it's meant to taste
    </p>
  </div>
</div>

<section class="section">
  <div class="container">

    <!-- Filter Tabs -->
    <div class="menu-filters" data-aos="fade-up">
      <?php foreach ($categories as $key => $cat): ?>
        <button class="menu-filter-btn <?= $key === 'all' ? 'active' : '' ?>"
                data-filter="<?= $cat['filter'] ?>">
          <?= $cat['label'] ?>
        </button>
      <?php endforeach; ?>
    </div>

    <!-- Items Grid -->
    <?php if (empty($allItems)): ?>
      <!-- Empty state — shown before admin adds menu items -->
      <div style="text-align:center;padding:4rem;background:white;border-radius:var(--radius-md);color:var(--text-muted);">
        <i class="fas fa-utensils" style="font-size:3rem;color:var(--flame-orange);display:block;margin-bottom:1rem;"></i>
        <h3 style="font-family:'Cinzel',serif;margin-bottom:0.5rem;">Menu Coming Soon</h3>
        <p>Add dishes from the <a href="/qoyla/admin/" style="color:var(--flame-orange);font-weight:700;">Admin Panel → Menu Items</a></p>
      </div>
    <?php else: ?>
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1.5rem;"
           id="menuGrid">
        <?php foreach ($allItems as $i => $item): ?>
          <div class="menu-item-wrapper"
               data-category="<?= e($item['category']) ?>"
               data-aos="fade-up"
               data-aos-delay="<?= ($i % 4) * 75 ?>">
            <div class="menu-card">
              <img src="<?= $item['image_path']
                            ? e($item['image_path'])
                            : 'https://placehold.co/400x300/1A1A1A/E8500A?text=' . urlencode($item['name']) ?>"
                   alt="<?= e($item['name']) ?>"
                   loading="lazy">
              <div class="menu-body">
                <div class="menu-name"><?= e($item['name']) ?></div>
                <div class="menu-desc"><?= e($item['description']) ?></div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                  <div class="menu-price">Rs. <?= number_format($item['price']) ?></div>
                  <?php if ($item['category'] === 'deals'): ?>
                    <span class="badge-orange" style="font-size:0.68rem;">DEAL</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</section>

<!-- Responsive grid fix for mobile -->
<style>
@media(max-width:992px){ #menuGrid{ grid-template-columns:repeat(3,1fr) !important; } }
@media(max-width:768px){ #menuGrid{ grid-template-columns:repeat(2,1fr) !important; } }
@media(max-width:480px){ #menuGrid{ grid-template-columns:repeat(1,1fr) !important; } }
</style>

<?php include 'includes/footer.php'; ?>
