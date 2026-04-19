<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About | Qoyla Restaurant</title>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="qoyla-nav">
  <div class="nav-inner">
    <a href="/qoyla/index.php" class="nav-brand">QOYLA<span>Restaurant · Multan</span></a>
    <div class="nav-links">
      <a href="/qoyla/index.php">Home</a><a href="/qoyla/menu.php">Menu</a><a href="/qoyla/gallery.php">Gallery</a>
      <a href="/qoyla/about.php" class="active">About</a><a href="/qoyla/contact.php">Contact</a>
      <a href="/qoyla/auth/login.php" class="nav-btn-login">Login</a>
    </div>
    <button class="nav-hamburger" id="navHamburger"><span></span><span></span><span></span></button>
  </div>
  <div class="nav-mobile" id="navMobile">
    <a href="/qoyla/index.php">Home</a><a href="/qoyla/menu.php">Menu</a><a href="/qoyla/gallery.php">Gallery</a>
    <a href="/qoyla/about.php">About</a><a href="/qoyla/contact.php">Contact</a><a href="/qoyla/auth/login.php">Login</a>
  </div>
</nav>

<!-- Hero -->
<div style="background:var(--charcoal-black); padding:4rem 0 3rem; text-align:center;">
  <div class="container">
    <h1 class="section-title center-line white-title" data-aos="fade-up">Our Story</h1>
    <p style="color:rgba(255,255,255,0.5); margin-top:1.5rem;" data-aos="fade-up" data-aos-delay="100">
      The people, the passion and the charcoal behind Qoyla
    </p>
  </div>
</div>

<!-- Owner Story -->
<section class="section">
  <div class="container">
    <div class="grid-2" style="align-items:center; gap:4rem;">
      <div data-aos="fade-right">
        <span class="badge-orange" style="margin-bottom:1.5rem; display:inline-block;">Founded in Multan</span>
        <h2 class="section-title">Born from a<br>Love of Charcoal</h2>
        <p style="color:var(--text-muted); line-height:1.9; margin-top:1.8rem; font-size:1rem;">
          Qoyla began with a simple obsession — food cooked the right way, over real charcoal, the way our ancestors cooked it.
          Our founder grew up watching his uncle run a tandoor in the old city lanes of Multan.
          The smell of qoyla, the hiss of meat hitting the grill — that never left him.
        </p>
        <p style="color:var(--text-muted); line-height:1.9; margin-top:1rem; font-size:1rem;">
          What started as a small setup in Multan has grown into a full restaurant experience — but the soul remains
          the same. Every dish still goes over the same charcoal. Every recipe still carries that story.
        </p>
        <div style="display:flex; gap:2rem; margin-top:2.5rem; flex-wrap:wrap;">
          <div><span style="font-family:'Cinzel',serif; font-size:2rem; font-weight:700; color:var(--flame-orange); display:block;">500+</span><span style="font-size:0.8rem; text-transform:uppercase; letter-spacing:1px; color:var(--text-muted);">Loyal Members</span></div>
          <div><span style="font-family:'Cinzel',serif; font-size:2rem; font-weight:700; color:var(--flame-orange); display:block;">30+</span><span style="font-size:0.8rem; text-transform:uppercase; letter-spacing:1px; color:var(--text-muted);">Signature Dishes</span></div>
          <div><span style="font-family:'Cinzel',serif; font-size:2rem; font-weight:700; color:var(--flame-orange); display:block;">100%</span><span style="font-size:0.8rem; text-transform:uppercase; letter-spacing:1px; color:var(--text-muted);">Real Charcoal</span></div>
        </div>
      </div>
      <div data-aos="fade-left">
        <img src="https://images.unsplash.com/photo-1466978913421-dad2ebd01d17?w=700&q=80"
             alt="Qoyla Kitchen"
             style="border-radius:var(--radius-lg); box-shadow:var(--shadow-lg); width:100%;">
      </div>
    </div>
  </div>
</section>

<!-- Why Qoyla -->
<section class="section section-mid">
  <div class="container">
    <div style="text-align:center; margin-bottom:3.5rem;">
      <h2 class="section-title center-line" data-aos="fade-up">Why Qoyla</h2>
    </div>
    <div class="grid-3">
      <div class="qoyla-card" style="padding:2rem; text-align:center;" data-aos="fade-up" data-aos-delay="0">
        <div class="feature-icon"><i class="fas fa-fire"></i></div>
        <h3 style="font-family:'Cinzel',serif; font-size:1.05rem; font-weight:700; margin-bottom:0.75rem;">Real Charcoal Only</h3>
        <p style="font-size:0.9rem; color:var(--text-muted); line-height:1.8;">No gas, no shortcuts. Every single dish goes over genuine charcoal — the flavour speaks for itself.</p>
      </div>
      <div class="qoyla-card" style="padding:2rem; text-align:center;" data-aos="fade-up" data-aos-delay="100">
        <div class="feature-icon"><i class="fas fa-mountain"></i></div>
        <h3 style="font-family:'Cinzel',serif; font-size:1.05rem; font-weight:700; margin-bottom:0.75rem;">Pahri Pathani Recipes</h3>
        <p style="font-size:0.9rem; color:var(--text-muted); line-height:1.8;">Authentic mountain-cuisine recipes — Dumpukht, Green Chicken, Khds Keema — you won't find these everywhere.</p>
      </div>
      <div class="qoyla-card" style="padding:2rem; text-align:center;" data-aos="fade-up" data-aos-delay="200">
        <div class="feature-icon"><i class="fas fa-crown"></i></div>
        <h3 style="font-family:'Cinzel',serif; font-size:1.05rem; font-weight:700; margin-bottom:0.75rem;">Loyalty That Rewards</h3>
        <p style="font-size:0.9rem; color:var(--text-muted); line-height:1.8;">Every visit earns you points. Redeem for discounts, special access, and more. Because you deserve it.</p>
      </div>
    </div>
  </div>
</section>

<!-- Our Staff -->
<section class="section">
  <div class="container">
    <div style="text-align:center; margin-bottom:3.5rem;">
      <h2 class="section-title center-line" data-aos="fade-up">Our Team</h2>
      <p class="section-sub centered" data-aos="fade-up" data-aos-delay="100">The hands that cook every meal</p>
    </div>
    <!-- NOTE FOR BACKEND: fetch from workers table WHERE show_on_site = 1 -->
    <div class="grid-4">
      <div class="qoyla-card" style="padding:2rem; text-align:center;" data-aos="fade-up" data-aos-delay="0">
        <img src="https://images.unsplash.com/photo-1583394293214-0b3e3c0b6b06?w=300&q=80" alt="Ustad Rasheed"
             class="team-avatar" loading="lazy">
        <h4 style="font-family:'Cinzel',serif;font-size:0.95rem;font-weight:700;margin-bottom:0.25rem;">Ustad Rasheed</h4>
        <p style="color:var(--flame-orange);font-size:0.8rem;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;">Head Chef</p>
        <p style="font-size:0.82rem;color:var(--text-muted);margin-top:0.5rem;line-height:1.6;">20 years over the charcoal. Dumpukht is his legacy.</p>
      </div>
      <div class="qoyla-card" style="padding:2rem; text-align:center;" data-aos="fade-up" data-aos-delay="100">
        <img src="https://images.unsplash.com/photo-1607631568010-a87245c0daf8?w=300&q=80" alt="Ahmed"
             style="width:88px;height:88px;border-radius:50%;object-fit:cover;border:3px solid var(--flame-orange);margin:0 auto 1rem;">
        <h4 style="font-family:'Cinzel',serif;font-size:0.95rem;font-weight:700;margin-bottom:0.25rem;">Ahmed Bhai</h4>
        <p style="color:var(--flame-orange);font-size:0.8rem;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;">Tandoor Specialist</p>
        <p style="font-size:0.82rem;color:var(--text-muted);margin-top:0.5rem;line-height:1.6;">Keeper of the qoyla. Every bread, every tikka — his touch.</p>
      </div>
      <div class="qoyla-card" style="padding:2rem; text-align:center;" data-aos="fade-up" data-aos-delay="200">
        <img src="https://images.unsplash.com/photo-1580489944761-15a19d654956?w=300&q=80" alt="Sana"
             style="width:88px;height:88px;border-radius:50%;object-fit:cover;border:3px solid var(--flame-orange);margin:0 auto 1rem;">
        <h4 style="font-family:'Cinzel',serif;font-size:0.95rem;font-weight:700;margin-bottom:0.25rem;">Sana</h4>
        <p style="color:var(--flame-orange);font-size:0.8rem;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;">Front of House</p>
        <p style="font-size:0.82rem;color:var(--text-muted);margin-top:0.5rem;line-height:1.6;">Your first smile at Qoyla. Making every guest feel at home.</p>
      </div>
      <div class="qoyla-card" style="padding:2rem; text-align:center;" data-aos="fade-up" data-aos-delay="300">
        <img src="https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?w=300&q=80" alt="Owner"
             style="width:88px;height:88px;border-radius:50%;object-fit:cover;border:3px solid var(--flame-orange);margin:0 auto 1rem;">
        <h4 style="font-family:'Cinzel',serif;font-size:0.95rem;font-weight:700;margin-bottom:0.25rem;">The Owner</h4>
        <p style="color:var(--flame-orange);font-size:0.8rem;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;">Founder</p>
        <p style="font-size:0.82rem;color:var(--text-muted);margin-top:0.5rem;line-height:1.6;">The vision, the obsession, the charcoal — all his.</p>
      </div>
    </div>
  </div>
</section>

<footer class="qoyla-footer">
  <div class="footer-inner">
    <div><div class="footer-brand-name">QOYLA</div><p class="footer-tagline">Authentic desi flavours, crafted over charcoal.</p>
      <div class="social-row"><a href="#" class="social-btn"><i class="fab fa-instagram"></i></a><a href="#" class="social-btn"><i class="fab fa-facebook-f"></i></a><a href="#" class="social-btn"><i class="fab fa-tiktok"></i></a><a href="#" class="social-btn"><i class="fab fa-whatsapp"></i></a></div>
    </div>
    <div><div class="footer-col-title">Navigate</div><div class="footer-links"><a href="/qoyla/index.php">Home</a><a href="/qoyla/menu.php">Menu</a><a href="/qoyla/gallery.php">Gallery</a><a href="/qoyla/about.php">About</a><a href="/qoyla/contact.php">Contact</a></div></div>
    <div><div class="footer-col-title">Reach Us</div>
      <div class="footer-contact-item"><i class="fas fa-phone"></i><span>+92 XXX XXXXXXX</span></div>
      <div class="footer-contact-item"><i class="fas fa-map-marker-alt"></i><span>Multan, Punjab</span></div>
    </div>
    <div><div class="footer-col-title">Loyalty Club</div><p style="font-size:0.88rem;line-height:1.8;margin-bottom:1.2rem;">Earn points on every visit.</p><a href="/qoyla/auth/signup.php" class="btn-qoyla" style="font-size:0.8rem;padding:0.55rem 1.3rem;">Join Now</a></div>
  </div>
  <div class="footer-bottom"><p>&copy; 2026 Qoyla Restaurant.</p><p>Made with 🔥 in Multan</p></div>
</footer>
<script src="assets/js/main.js"></script>
</body>
</html>
