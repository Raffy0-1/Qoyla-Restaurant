<?php
// ============================================================
// QOYLA — HOME PAGE
// File: index.php  (replaces index.html)
// ============================================================
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle  = 'Qoyla Restaurant | Desi Flavours, Multan';
$activePage = 'home';
include 'includes/header.php';

// ---- Fetch live data from DB ----
$deals    = $pdo->query("SELECT * FROM deals WHERE is_active = 1 ORDER BY id DESC LIMIT 6")->fetchAll();
$featured = $pdo->query("SELECT * FROM menu_items WHERE is_featured = 1 AND is_available = 1 LIMIT 4")->fetchAll();

// Deal type icon map — Font Awesome classes
$dealIcons = [
    'package'      => 'fa-gift',
    'weekend'      => 'fa-calendar-star',
    'game'         => 'fa-gamepad',
    'service'      => 'fa-bolt',
    'announcement' => 'fa-bullhorn',
    'special'      => 'fa-star',
];
?>

<!-- ============================================================
     HERO SECTION
     ============================================================ -->
<section class="hero-section">
  <div class="hero-content">
    <div style="max-width:620px;">
      <div class="hero-eyebrow" data-aos="fade-down">
        <i class="fas fa-fire"></i> Est. Multan · Charcoal Grilled
      </div>
      <h1 class="hero-title" data-aos="fade-right" data-aos-delay="100">
        The Taste of
        <span class="highlight">Real Charcoal</span>
      </h1>
      <p class="hero-subtitle" data-aos="fade-right" data-aos-delay="200">
        Authentic desi dishes slow-cooked over glowing qoyla.
        Meats, breads, and flavours your dadi would approve.
      </p>
      <div class="hero-cta" data-aos="fade-up" data-aos-delay="300">
        <a href="/qoyla/menu.php" class="btn-qoyla">
          <i class="fas fa-utensils"></i> Explore Menu
        </a>
        <a href="/qoyla/auth/signup.php" class="btn-qoyla-ghost">
          <i class="fas fa-star"></i> Join Loyalty Club
        </a>
      </div>
      <div class="hero-stats" data-aos="fade-up" data-aos-delay="400">
        <?php
        // Live stats from DB
        $customerCount = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
        $menuCount     = $pdo->query("SELECT COUNT(*) FROM menu_items WHERE is_available=1")->fetchColumn();
        ?>
        <div>
          <span class="hero-stat-num"><?= $customerCount ?>+</span>
          <span class="hero-stat-label">Loyal Members</span>
        </div>
        <div style="width:1px;background:rgba(255,255,255,0.12);"></div>
        <div>
          <span class="hero-stat-num"><?= $menuCount ?>+</span>
          <span class="hero-stat-label">Dishes Available</span>
        </div>
        <div style="width:1px;background:rgba(255,255,255,0.12);"></div>
        <div>
          <span class="hero-stat-num">5★</span>
          <span class="hero-stat-label">Guest Rating</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     ACTIVE DEALS
     ============================================================ -->
<section class="section" style="background:var(--off-white);">
  <div class="container">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:1rem;margin-bottom:3.5rem;">
      <div>
        <h2 class="section-title" data-aos="fade-right">Active Deals</h2>
        <p class="section-sub" style="margin-top:1.5rem;" data-aos="fade-right" data-aos-delay="100">
          Earn points, save money, eat more
        </p>
      </div>
      <a href="/qoyla/menu.php" class="btn-qoyla-outline" data-aos="fade-left">
        View Full Menu
      </a>
    </div>

    <?php if (empty($deals)): ?>
      <!-- No deals in DB yet — show placeholder message -->
      <div style="text-align:center;padding:3rem;background:white;border-radius:var(--radius-md);color:var(--text-muted);">
        <i class="fas fa-tags" style="font-size:2.5rem;color:var(--flame-orange);display:block;margin-bottom:1rem;"></i>
        <p>No active deals right now. Add deals from the <a href="/qoyla/admin/" style="color:var(--flame-orange);font-weight:700;">Admin Panel</a>.</p>
      </div>
    <?php else: ?>
      <div class="grid-3" style="gap:1.5rem;">
        <?php foreach ($deals as $i => $deal): ?>
        <div class="deal-card" onclick="fetchAndShowItem('deal', <?= $deal['id'] ?>)" style="cursor:pointer;" data-aos="fade-up" data-aos-delay="<?= $i * 100 ?>">
          <?php if (!empty($deal['image_path'])): ?>
            <img src="<?= e($deal['image_path']) ?>" alt="<?= e($deal['title']) ?>" class="deal-card-img">
          <?php endif; ?>
          <span class="deal-ribbon"><?= ucfirst(e($deal['deal_type'])) ?></span>
          <div class="deal-body">
            <?php if (empty($deal['image_path'])): ?>
            <div class="deal-icon-fa">
              <i class="fas <?= $dealIcons[$deal['deal_type']] ?? 'fa-fire' ?>"></i>
            </div>
            <?php endif; ?>
            <div class="deal-title"><?= e($deal['title']) ?></div>
            <div class="deal-desc"><?= e($deal['description']) ?></div>
            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
              <?php if ($deal['discount_percent'] > 0): ?>
                <span class="badge-orange"><?= $deal['discount_percent'] ?>% OFF</span>
              <?php endif; ?>
              <?php if ($deal['points_multiplier'] > 1): ?>
                <span class="badge-orange"><?= $deal['points_multiplier'] ?>× Points</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- ============================================================
     FEATURED MENU TEASER
     ============================================================ -->
<section class="section section-mid">
  <div class="container">
    <div style="text-align:center;margin-bottom:3.5rem;">
      <h2 class="section-title center-line" data-aos="fade-up">From Our Kitchen</h2>
      <p class="section-sub centered" data-aos="fade-up" data-aos-delay="100">
        A glimpse of what awaits on every visit
      </p>
    </div>

    <?php if (empty($featured)): ?>
      <div style="text-align:center;padding:3rem;background:white;border-radius:var(--radius-md);color:var(--text-muted);">
        <i class="fas fa-utensils" style="font-size:2.5rem;color:var(--flame-orange);display:block;margin-bottom:1rem;"></i>
        <p>No featured items yet. Mark items as featured in the <a href="/qoyla/admin/" style="color:var(--flame-orange);font-weight:700;">Admin Panel</a>.</p>
      </div>
    <?php else: ?>
      <div class="grid-4" data-aos="fade-up" data-aos-delay="150">
        <?php foreach ($featured as $item): ?>
        <div class="menu-card" onclick="fetchAndShowItem('menu', <?= $item['id'] ?>)" style="cursor:pointer;">
          <img src="<?= $item['image_path'] ? e($item['image_path']) : 'https://placehold.co/400x300/1A1A1A/E8500A?text=' . urlencode($item['name']) ?>"
               alt="<?= e($item['name']) ?>" loading="lazy">
          <div class="menu-body">
            <div class="menu-name"><?= e($item['name']) ?></div>
            <div class="menu-desc"><?= e($item['description']) ?></div>
            <div class="menu-price">Rs. <?= number_format($item['price']) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div style="text-align:center;margin-top:3rem;" data-aos="fade-up">
      <a href="/qoyla/menu.php" class="btn-qoyla">
        See Full Menu <i class="fas fa-arrow-right"></i>
      </a>
    </div>
  </div>
</section>

<!-- ============================================================
     GALLERY TEASER (static — no DB needed)
     ============================================================ -->
<section class="section section-dark">
  <div class="container">
    <div style="text-align:center;margin-bottom:3rem;">
      <h2 class="section-title center-line white-title" data-aos="fade-up">Qoyla in Action</h2>
      <p class="section-sub centered" style="color:rgba(255,255,255,0.55);" data-aos="fade-up" data-aos-delay="100">
        Game nights, chef specials, events & unforgettable moments
      </p>
    </div>
    <div class="gallery-grid" style="grid-template-columns:repeat(3,1fr);max-width:900px;margin:0 auto 2.5rem;" data-aos="fade-up" data-aos-delay="200">
      <div class="gallery-item" style="aspect-ratio:4/3;">
        <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=600&q=80" alt="Qoyla Scene">
        <div class="gallery-overlay"><span class="gallery-caption">Qoyla Evenings</span></div>
      </div>
      <div class="gallery-item" style="aspect-ratio:4/3;">
        <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=600&q=80" alt="Game Night">
        <div class="gallery-overlay"><span class="gallery-caption">Game Nights</span></div>
      </div>
      <div class="gallery-item" style="aspect-ratio:4/3;">
        <img src="https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=600&q=80" alt="Chef Special">
        <div class="gallery-overlay"><span class="gallery-caption">Chef Specials</span></div>
      </div>
    </div>
    <div style="text-align:center;" data-aos="fade-up">
      <a href="/qoyla/gallery.php" class="btn-qoyla">
        View Full Gallery <i class="fas fa-images"></i>
      </a>
    </div>
  </div>
</section>

<!-- ============================================================
     LOYALTY CTA STRIP
     ============================================================ -->
<section style="background:var(--flame-orange);padding:4rem 0;">
  <div class="container" style="text-align:center;">
    <h2 style="font-family:'Cinzel',serif;font-size:clamp(1.6rem,3vw,2.4rem);color:white;margin-bottom:0.75rem;" data-aos="fade-up">
      Join the Qoyla Loyalty Club
    </h2>
    <p style="color:rgba(255,255,255,0.85);font-size:1.05rem;margin-bottom:2rem;max-width:500px;margin-left:auto;margin-right:auto;" data-aos="fade-up" data-aos-delay="100">
      Earn points on every visit. Redeem for discounts, hotel transfers, and exclusive deals.
    </p>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;" data-aos="fade-up" data-aos-delay="200">
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="/qoyla/dashboard/index.php" class="btn-qoyla" style="background:white;color:var(--flame-orange);border-color:white;">
          <i class="fas fa-star"></i> Go to My Dashboard
        </a>
      <?php else: ?>
        <a href="/qoyla/auth/signup.php" class="btn-qoyla" style="background:white;color:var(--flame-orange);border-color:white;">
          <i class="fas fa-star"></i> Sign Up Free
        </a>
        <a href="/qoyla/auth/login.php" class="btn-qoyla-ghost">Already a Member? Login</a>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
