<?php
// ============================================================
// QOYLA — CONTACT PAGE
// File: contact.php  (replaces contact.html)
// Both contact form AND complaint box handled here
// ============================================================
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle  = 'Contact | Qoyla Restaurant';
$activePage = 'contact';

// ---- Handle Form Submission (POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $name      = trim($_POST['name']      ?? '');
    $phone     = trim($_POST['phone']     ?? '');
    $email     = trim($_POST['email']     ?? '');
    $message   = trim($_POST['message']   ?? '');
    $form_type = $_POST['form_type'] === 'complaint' ? 'complaint' : 'contact';

    // Validate
    if (empty($name) || empty($message)) {
        setFlash('error', 'Please fill in your name and message.');
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO contact_messages (name, phone, email, message, form_type)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $phone, $email, $message, $form_type]);

        if ($form_type === 'complaint') {
            setFlash('success', 'Your complaint has been submitted. Management will review it shortly.');
        } else {
            setFlash('success', 'Message sent! We\'ll get back to you as soon as possible.');
        }
        // POST → Redirect → GET  (prevents resubmit on refresh)
        header('Location: /qoyla/contact.php');
        exit;
    }
}

include 'includes/header.php';
?>

<!-- Page Hero -->
<div style="background:var(--charcoal-black);padding:4rem 0 3rem;text-align:center;">
  <div class="container">
    <h1 class="section-title center-line white-title" data-aos="fade-up">Contact Us</h1>
    <p style="color:rgba(255,255,255,0.5);margin-top:1.5rem;" data-aos="fade-up" data-aos-delay="100">
      We're always here — reach out any time
    </p>
  </div>
</div>

<section class="section">
  <div class="container">

    <!-- Flash message (shows after redirect) -->
    <?= getFlash() ?>

    <div class="grid-2" style="gap:4rem;align-items:start;">

      <!-- LEFT: Contact Info -->
      <div data-aos="fade-right">
        <h2 class="section-title" style="margin-bottom:2rem;">Get in Touch</h2>

        <div style="display:flex;flex-direction:column;gap:1.25rem;margin-bottom:2.5rem;">

          <a href="tel:+923001234567"
             style="display:flex;align-items:center;gap:1rem;padding:1.1rem 1.25rem;background:white;border-radius:var(--radius-md);box-shadow:var(--shadow-sm);color:var(--text-body);transition:all 0.3s;"
             onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform=''">
            <div style="width:44px;height:44px;border-radius:12px;background:var(--flame-glow);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fas fa-phone" style="color:var(--flame-orange);"></i>
            </div>
            <div>
              <div style="font-size:0.72rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--text-muted);margin-bottom:2px;">Phone</div>
              <div style="font-weight:700;">+92 300 1234567</div>
            </div>
          </a>

          <a href="mailto:info@qoyla.pk"
             style="display:flex;align-items:center;gap:1rem;padding:1.1rem 1.25rem;background:white;border-radius:var(--radius-md);box-shadow:var(--shadow-sm);color:var(--text-body);transition:all 0.3s;"
             onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform=''">
            <div style="width:44px;height:44px;border-radius:12px;background:var(--flame-glow);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fas fa-envelope" style="color:var(--flame-orange);"></i>
            </div>
            <div>
              <div style="font-size:0.72rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--text-muted);margin-bottom:2px;">Email</div>
              <div style="font-weight:700;">info@qoyla.pk</div>
            </div>
          </a>

          <a href="https://wa.me/923001234567"
             style="display:flex;align-items:center;gap:1rem;padding:1.1rem 1.25rem;background:white;border-radius:var(--radius-md);box-shadow:var(--shadow-sm);color:var(--text-body);transition:all 0.3s;"
             onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform=''">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(37,211,102,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fab fa-whatsapp" style="color:#25D366;"></i>
            </div>
            <div>
              <div style="font-size:0.72rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--text-muted);margin-bottom:2px;">WhatsApp</div>
              <div style="font-weight:700;">Message Us</div>
            </div>
          </a>

          <div style="display:flex;align-items:center;gap:1rem;padding:1.1rem 1.25rem;background:white;border-radius:var(--radius-md);box-shadow:var(--shadow-sm);">
            <div style="width:44px;height:44px;border-radius:12px;background:var(--flame-glow);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fas fa-map-marker-alt" style="color:var(--flame-orange);"></i>
            </div>
            <div>
              <div style="font-size:0.72rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--text-muted);margin-bottom:2px;">Location</div>
              <div style="font-weight:700;">Multan, Punjab, Pakistan</div>
            </div>
          </div>
        </div>

        <!-- Google Maps placeholder -->
        <!-- REPLACE the div below with your real Google Maps iframe when ready -->
        <div style="background:#E8E2DA;border-radius:var(--radius-md);height:240px;display:flex;align-items:center;justify-content:center;overflow:hidden;">
          <div style="text-align:center;color:var(--text-muted);">
            <i class="fas fa-map-marked-alt" style="font-size:2.5rem;color:var(--flame-orange);margin-bottom:0.75rem;display:block;"></i>
            <p style="font-weight:700;font-size:0.9rem;">Add your Google Maps embed here</p>
            <p style="font-size:0.8rem;">Replace this div with &lt;iframe src="your google maps link"&gt;</p>
          </div>
        </div>
      </div>

      <!-- RIGHT: Forms -->
      <div data-aos="fade-left">

        <!-- CONTACT FORM -->
        <div style="background:white;border-radius:var(--radius-lg);padding:2rem;box-shadow:var(--shadow-sm);margin-bottom:2rem;">
          <h3 style="font-family:'Cinzel',serif;font-size:1.1rem;font-weight:700;margin-bottom:1.5rem;">
            Send a Message
          </h3>
          <form method="POST" action="/qoyla/contact.php" data-loading>
            <input type="hidden" name="form_type" value="contact">
            <div class="form-group">
              <label class="form-label">Your Name *</label>
              <input type="text" name="name" class="form-input"
                     placeholder="e.g. Ali Hassan" required
                     value="<?= e($_POST['name'] ?? '') ?>">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
              <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="tel" name="phone" class="form-input"
                       placeholder="03XX XXXXXXX"
                       value="<?= e($_POST['phone'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input"
                       placeholder="your@email.com"
                       value="<?= e($_POST['email'] ?? '') ?>">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Message *</label>
              <textarea name="message" class="form-textarea"
                        placeholder="Write your message here..." required><?= e($_POST['message'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn-qoyla" style="width:100%;justify-content:center;">
              Send Message <i class="fas fa-paper-plane"></i>
            </button>
          <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
</form>
        </div>

        <!-- COMPLAINT BOX — separate form, same page, different form_type -->
        <div style="background:white;border-radius:var(--radius-lg);padding:2rem;box-shadow:var(--shadow-sm);border-left:5px solid var(--flame-orange);">
          <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem;">
            <div style="width:36px;height:36px;border-radius:10px;background:var(--flame-glow);display:flex;align-items:center;justify-content:center;">
              <i class="fas fa-exclamation-triangle" style="color:var(--flame-orange);font-size:0.9rem;"></i>
            </div>
            <h3 style="font-family:'Cinzel',serif;font-size:1.05rem;font-weight:700;margin:0;">
              Submit a Complaint
            </h3>
          </div>
          <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:1.5rem;line-height:1.7;">
            We take every complaint seriously. All submissions go directly to management.
          </p>
          <form method="POST" action="/qoyla/contact.php">
            <input type="hidden" name="form_type" value="complaint">
            <div class="form-group">
              <label class="form-label">Your Name *</label>
              <input type="text" name="name" class="form-input" placeholder="Your full name" required>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
              <div class="form-group">
                <label class="form-label">Phone *</label>
                <input type="tel" name="phone" class="form-input" placeholder="0300 1234567" required>
              </div>
              <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input" placeholder="Optional">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Complaint Details *</label>
              <textarea name="message" class="form-textarea" style="min-height:100px;"
                        placeholder="Describe your concern in detail..." required></textarea>
            </div>
            <button type="submit" class="btn-qoyla-outline" style="width:100%;justify-content:center;">
              Submit Complaint <i class="fas fa-shield-alt"></i>
            </button>
          <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
</form>
        </div>

      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
