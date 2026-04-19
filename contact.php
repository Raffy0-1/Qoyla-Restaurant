<?php
// ============================================================
// QOYLA — CONTACT PAGE
// File: contact.php  (replaces contact.html)
// Both contact form AND complaint box handled here
// ============================================================
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle  = 'Contact Us | Qoyla Restaurant Multan';
$metaDescription = 'Get in touch with Qoyla Restaurant. Find our location in Multan, book a table, or send us your feedback. Authentic Pakistani charcoal cuisine at your fingertips.';
$activePage = 'contact';

include 'includes/header.php';

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

        if (!empty($email)) {
            $subject = $form_type === 'complaint' ? 'Qoyla: Your Complaint has been Received' : 'Qoyla: We received your message';
            $bodyHTML = "
                <h2 style='color:#E8500A'>Hello " . e($name) . ",</h2>
                <p>We have received your " . ($form_type === 'complaint' ? "complaint" : "message") . " and our team will review it shortly.</p>
                <p><strong>You wrote:</strong><br/><i>" . nl2br(e($message)) . "</i></p>
                <p>Thank you for reaching out to Qoyla.</p>
            ";
            sendQoylaEmail($email, $subject, $bodyHTML);
        }

        $adminEmail = 'admin@qoyla.pk'; 
        $adminSubject = "New " . ucfirst($form_type) . " received from " . e($name);
        $adminBody = "
            <h2 style='color:#E8500A'>New " . ucfirst($form_type) . "</h2>
            <p><strong>Name:</strong> " . e($name) . "</p>
            <p><strong>Phone:</strong> " . e($phone) . "</p>
            <p><strong>Email:</strong> " . e($email) . "</p>
            <p><strong>Message:</strong><br/>" . nl2br(e($message)) . "</p>
            <a href='https://qoyla.pk/admin/index.php?page=messages' style='background:#E8500A;color:#fff;padding:10px;text-decoration:none;'>View in Admin Panel</a>
        ";
        sendQoylaEmail($adminEmail, $adminSubject, $adminBody);

        if ($form_type === 'complaint') {
            setFlash('success', 'Your complaint has been submitted. Management will review it shortly.');
        } else {
            setFlash('success', 'Message sent! We\'ll get back to you as soon as possible.');
        }
        // POST → Redirect → GET  (prevents resubmit on refresh)
        header('Location: ' . BASE_URL . 'contact.php');
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

          <!-- PLACEHOLDER: Replace href with real phone number: tel:+92XXXXXXXXXX -->
          <a href="tel:+92XXXXXXXXXX"
             style="display:flex;align-items:center;gap:1rem;padding:1.1rem 1.25rem;background:white;border-radius:var(--radius-md);box-shadow:var(--shadow-sm);color:var(--text-body);transition:var(--transition);"
             class="contact-info-link">
            <div style="width:44px;height:44px;border-radius:12px;background:var(--flame-glow);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fas fa-phone" style="color:var(--flame-orange);"></i>
            </div>
            <div>
              <div style="font-size:0.72rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--text-muted);margin-bottom:2px;">Phone</div>
              <!-- PLACEHOLDER: Replace with real phone number -->
              <div style="font-weight:700;">+92 XXX XXXXXXX <span class="info-placeholder-note">Set before launch</span></div>
            </div>
          </a>

          <!-- PLACEHOLDER: Replace href with real email: mailto:real@domain.pk -->
          <a href="mailto:info@qoyla.pk"
             style="display:flex;align-items:center;gap:1rem;padding:1.1rem 1.25rem;background:white;border-radius:var(--radius-md);box-shadow:var(--shadow-sm);color:var(--text-body);transition:var(--transition);"
             class="contact-info-link">
            <div style="width:44px;height:44px;border-radius:12px;background:var(--flame-glow);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fas fa-envelope" style="color:var(--flame-orange);"></i>
            </div>
            <div>
              <div style="font-size:0.72rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--text-muted);margin-bottom:2px;">Email</div>
              <!-- PLACEHOLDER: Replace with real email address -->
              <div style="font-weight:700;">info@qoyla.pk <span class="info-placeholder-note">Set before launch</span></div>
            </div>
          </a>

          <!-- PLACEHOLDER: Replace href with real WhatsApp: https://wa.me/92XXXXXXXXXX -->
          <a href="https://wa.me/923001234567"
             style="display:flex;align-items:center;gap:1rem;padding:1.1rem 1.25rem;background:white;border-radius:var(--radius-md);box-shadow:var(--shadow-sm);color:var(--text-body);transition:var(--transition);"
             class="contact-info-link" target="_blank" rel="noopener noreferrer">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(37,211,102,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fab fa-whatsapp" style="color:#25D366;"></i>
            </div>
            <div>
              <div style="font-size:0.72rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--text-muted);margin-bottom:2px;">WhatsApp</div>
              <!-- PLACEHOLDER: Replace with real WhatsApp number -->
              <div style="font-weight:700;">Message Us <span class="info-placeholder-note">Set before launch</span></div>
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
        <!-- Google Maps replaced with OpenStreetMap embed (no API key required) -->
        <!-- PLACEHOLDER: Update coordinates (lat, lon) and marker to match real restaurant location -->
        <div class="map-embed-container">
          <iframe
            src="https://www.openstreetmap.org/export/embed.html?bbox=71.4749%2C30.1075%2C71.5749%2C30.2075&layer=mapnik&marker=30.1575%2C71.5249"
            title="Qoyla Restaurant Location — Multan"
            allowfullscreen
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade">
          </iframe>
        </div>
      </div>

      <!-- RIGHT: Forms -->
      <div data-aos="fade-left">

        <!-- CONTACT FORM -->
        <div style="background:white;border-radius:var(--radius-lg);padding:2rem;box-shadow:var(--shadow-sm);margin-bottom:2rem;">
          <h3 style="font-family:'Cinzel',serif;font-size:1.1rem;font-weight:700;margin-bottom:1.5rem;">
            Send a Message
          </h3>
          <form method="POST" action="<?= BASE_URL ?>contact.php" data-loading>
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
          <form method="POST" action="<?= BASE_URL ?>contact.php">
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
