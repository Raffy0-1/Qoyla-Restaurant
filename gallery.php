<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gallery | Qoyla Restaurant</title>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="qoyla-nav">
  <div class="nav-inner">
    <a href="/qoyla/index.php" class="nav-brand">QOYLA<span>Restaurant · Multan</span></a>
    <div class="nav-links">
      <a href="/qoyla/index.php">Home</a><a href="/qoyla/menu.php">Menu</a>
      <a href="/qoyla/gallery.php" class="active">Gallery</a>
      <a href="/qoyla/about.php">About</a><a href="/qoyla/contact.php">Contact</a>
      <a href="/qoyla/auth/login.php" class="nav-btn-login">Login</a>
    </div>
    <button class="nav-hamburger" id="navHamburger"><span></span><span></span><span></span></button>
  </div>
  <div class="nav-mobile" id="navMobile">
    <a href="/qoyla/index.php">Home</a><a href="/qoyla/menu.php">Menu</a><a href="/qoyla/gallery.php">Gallery</a>
    <a href="/qoyla/about.php">About</a><a href="/qoyla/contact.php">Contact</a><a href="/qoyla/auth/login.php">Login</a>
  </div>
</nav>

<div style="background:var(--charcoal-black); padding:4rem 0 3rem; text-align:center;">
  <div class="container">
    <h1 class="section-title center-line white-title" data-aos="fade-up">Gallery</h1>
    <p style="color:rgba(255,255,255,0.5); margin-top:1.5rem;" data-aos="fade-up" data-aos-delay="100">
      Scenes, events, special nights & moments from Qoyla
    </p>
  </div>
</div>

<section class="section">
  <div class="container">

    <!-- Filter Bar -->
    <div class="gallery-filter-bar" data-aos="fade-up">
      <button class="gallery-filter-btn active" data-filter="all">All</button>
      <button class="gallery-filter-btn" data-filter="scenes">Qoyla Scenes</button>
      <button class="gallery-filter-btn" data-filter="games">Game Nights</button>
      <button class="gallery-filter-btn" data-filter="events">Events</button>
      <button class="gallery-filter-btn" data-filter="nights">Special Nights</button>
      <button class="gallery-filter-btn" data-filter="chef">Chef Specials</button>
    </div>

    <!-- Gallery Grid -->
    <!-- NOTE FOR BACKEND: loop through gallery_images table, each item has category + caption -->
    <div class="gallery-grid" data-aos="fade-up" data-aos-delay="100">

      <div class="gallery-item-wrap" data-category="scenes">
        <div class="gallery-item">
          <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=600&q=80" alt="Qoyla Dining">
          <div class="gallery-overlay"><span class="gallery-caption">Qoyla Dining Hall</span></div>
        </div>
      </div>

      <div class="gallery-item-wrap" data-category="games">
        <div class="gallery-item">
          <img src="https://images.unsplash.com/photo-1529543544282-ea669407fca3?w=600&q=80" alt="Game Night">
          <div class="gallery-overlay"><span class="gallery-caption">Thursday Game Night</span></div>
        </div>
      </div>

      <div class="gallery-item-wrap" data-category="chef">
        <div class="gallery-item">
          <img src="https://images.unsplash.com/photo-1556910103-1c02745aae4d?w=600&q=80" alt="Chef at Work">
          <div class="gallery-overlay"><span class="gallery-caption">Ustad Rasheed in Action</span></div>
        </div>
      </div>

      <div class="gallery-item-wrap" data-category="nights">
        <div class="gallery-item">
          <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=600&q=80" alt="Special Night">
          <div class="gallery-overlay"><span class="gallery-caption">Qoyla Special Night</span></div>
        </div>
      </div>

      <div class="gallery-item-wrap" data-category="scenes">
        <div class="gallery-item">
          <img src="https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=600&q=80" alt="Restaurant Interior">
          <div class="gallery-overlay"><span class="gallery-caption">The Qoyla Atmosphere</span></div>
        </div>
      </div>

      <div class="gallery-item-wrap" data-category="chef">
        <div class="gallery-item">
          <img src="https://images.unsplash.com/photo-1600565193348-f74bd3c7ccdf?w=600&q=80" alt="Chef Special Dish">
          <div class="gallery-overlay"><span class="gallery-caption">Chef's Special — Dumpukht</span></div>
        </div>
      </div>

      <div class="gallery-item-wrap" data-category="events">
        <div class="gallery-item">
          <img src="https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=600&q=80" alt="Private Event">
          <div class="gallery-overlay"><span class="gallery-caption">Private Gathering</span></div>
        </div>
      </div>

      <div class="gallery-item-wrap" data-category="games">
        <div class="gallery-item">
          <img src="https://images.unsplash.com/photo-1543269865-cbf427effbad?w=600&q=80" alt="Friends at Qoyla">
          <div class="gallery-overlay"><span class="gallery-caption">Friends & Food</span></div>
        </div>
      </div>

      <div class="gallery-item-wrap" data-category="nights">
        <div class="gallery-item">
          <img src="https://images.unsplash.com/photo-1574966739987-65a60a00bf23?w=600&q=80" alt="Night Ambience">
          <div class="gallery-overlay"><span class="gallery-caption">Evening Ambience</span></div>
        </div>
      </div>

      <div class="gallery-item-wrap" data-category="scenes">
        <div class="gallery-item">
          <img src="https://images.unsplash.com/photo-1466978913421-dad2ebd01d17?w=600&q=80" alt="The Tandoor">
          <div class="gallery-overlay"><span class="gallery-caption">The Qoyla Tandoor</span></div>
        </div>
      </div>

      <div class="gallery-item-wrap" data-category="events">
        <div class="gallery-item">
          <img src="https://images.unsplash.com/photo-1533777857889-4be7c70b33f7?w=600&q=80" alt="Family Event">
          <div class="gallery-overlay"><span class="gallery-caption">Family Celebration</span></div>
        </div>
      </div>

      <div class="gallery-item-wrap" data-category="chef">
        <div class="gallery-item">
          <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600&q=80" alt="Signature Dish">
          <div class="gallery-overlay"><span class="gallery-caption">Signature Platter</span></div>
        </div>
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
