<?php
// ============================================================
// QOYLA — CUSTOMER DASHBOARD
// File: dashboard/index.php
// ============================================================
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php'; // kicks out if not logged in

$pageTitle = 'My Dashboard | Qoyla Loyalty';

// ---- Fetch all customer data ----
$userId = $_SESSION['user_id'];

// Customer record
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$userId]);
$customer = $stmt->fetch();

if (!$customer) {
    // Should not happen, but safety first
    session_destroy();
    header('Location: /qoyla/auth/login.php');
    exit;
}

// Last 3 visits
$stmt = $pdo->prepare("
    SELECT * FROM visits
    WHERE customer_id = ?
    ORDER BY visit_date DESC
    LIMIT 3
");
$stmt->execute([$userId]);
$recentVisits = $stmt->fetchAll();

// Points history last 30 days
$stmt = $pdo->prepare("
    SELECT * FROM points_history
    WHERE customer_id = ?
      AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ORDER BY date DESC
");
$stmt->execute([$userId]);
$transferHistory = $stmt->fetchAll();

// Active deals
$deals = $pdo->query("SELECT * FROM deals WHERE is_active = 1 ORDER BY id DESC")->fetchAll();

// Handle Transfer Points form submission
$transferError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'transfer') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    
    $points       = (int)($_POST['points'] ?? 0);
    $transferType = $_POST['transfer_type'] ?? 'admin';

    if ($points <= 0) {
        $transferError = 'Please enter a valid number of points.';
    } elseif ($points > $customer['total_points']) {
        $transferError = 'You don\'t have enough points.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Deduct points from customer
            $pdo->prepare("UPDATE customers SET total_points = total_points - ? WHERE id = ?")
                ->execute([$points, $userId]);

            // Log in history
            $desc = 'Transferred to ' . ucfirst($transferType);
            $pdo->prepare("
                INSERT INTO points_history (customer_id, points, type, description, date)
                VALUES (?, ?, 'transferred', ?, CURDATE())
            ")->execute([$userId, $points, $desc]);

            $pdo->commit();

            // Refresh customer data
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->execute([$userId]);
            $customer = $stmt->fetch();

            setFlash('success', $points . ' points transferred successfully!');
            header('Location: /qoyla/dashboard/index.php');
            exit;
        } catch (\Exception $e) {
            $pdo->rollBack();
            $transferError = 'An error occurred during transfer. Please try again.';
        }
    }
}

// Points rupee value
$pointsValue = number_format($customer['total_points'] * POINT_VALUE, 0);

// Sr No formatted
$srFormatted = '#' . str_pad($customer['sr_no'], 3, '0', STR_PAD_LEFT);
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

<!-- Dashboard Navbar (different from public navbar) -->
<nav class="qoyla-nav">
  <div class="nav-inner">
    <a href="/qoyla/index.php" class="nav-brand">QOYLA<span>Loyalty Dashboard</span></a>
    <div class="nav-links">
      <a href="/qoyla/index.php">Home</a>
      <a href="/qoyla/menu.php">Menu</a>
      <a href="/qoyla/dashboard/index.php" class="active">My Dashboard</a>
      <a href="/qoyla/auth/logout.php"
         class="nav-btn-login"
         style="background:var(--charcoal-light);">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>
    <button class="nav-hamburger" id="navHamburger">
      <span></span><span></span><span></span>
    </button>
  </div>
  <div class="nav-mobile" id="navMobile">
    <a href="/qoyla/index.php">Home</a>
    <a href="/qoyla/menu.php">Menu</a>
    <a href="/qoyla/dashboard/index.php">Dashboard</a>
    <a href="/qoyla/auth/logout.php">Logout</a>
  </div>
</nav>

<div class="page-flex-1" style="background:var(--off-white);padding:2.5rem 0;">
  <div class="container">

    <?= getFlash() ?>

    <?php if ($transferError): ?>
      <div class="flash flash-error">
        <i class="fas fa-exclamation-circle"></i> <?= e($transferError) ?>
      </div>
    <?php endif; ?>

    <!-- Welcome Banner -->
    <div class="dashboard-banner" data-aos="fade-down">
      <h2>Welcome back, <?= e($customer['name']) ?> 👋</h2>
      <p>
        Qoyla Loyalty Member &nbsp;·&nbsp;
        <strong style="color:var(--flame-orange);"><?= $srFormatted ?></strong>
        &nbsp;·&nbsp;
        Member since <?= date('M Y', strtotime($customer['created_at'])) ?>
      </p>
    </div>

    <!-- Row 1: Points + Visits -->
    <div class="dash-layout">

      <!-- LOYALTY POINTS -->
      <div class="stat-card" style="text-align:center;padding:2.5rem 2rem;" data-aos="fade-up">
        <i class="fas fa-fire" style="font-size:2.5rem;color:var(--flame-orange);display:block;margin-bottom:1rem;"></i>
        <div style="font-size:0.72rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--text-muted);margin-bottom:0.5rem;">
          Your Points
        </div>
        <div class="points-display" style="font-size:3rem;margin-bottom:0.3rem;">
          <?= number_format($customer['total_points']) ?>
        </div>
        <div style="font-size:0.85rem;color:var(--text-muted);margin-bottom:2rem;">
          ≈ Rs. <?= $pointsValue ?> discount value
        </div>
        <button class="btn-qoyla" style="width:100%;justify-content:center;"
                onclick="openModal('transferModal')">
          <i class="fas fa-paper-plane"></i> Transfer Points
        </button>
      </div>

      <!-- RECENT VISITS -->
      <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
        <h4>Recent Visits <span style="font-size:0.72rem;color:var(--text-muted);font-weight:400;text-transform:none;letter-spacing:0;">(last 3)</span></h4>

        <?php if (empty($recentVisits)): ?>
          <div style="text-align:center;padding:2rem;color:var(--text-muted);">
            <i class="fas fa-calendar-times" style="font-size:2rem;margin-bottom:0.75rem;display:block;color:var(--flame-orange);opacity:0.4;"></i>
            <p style="font-size:0.9rem;">No visits recorded yet. Come visit us and start earning! 🔥</p>
          </div>
        <?php else: ?>
          <div style="display:flex;flex-direction:column;gap:0.75rem;">
            <?php foreach ($recentVisits as $v): ?>
              <div style="display:flex;justify-content:space-between;align-items:center;padding:0.85rem 1rem;background:var(--off-white);border-radius:var(--radius-sm);">
                <div>
                  <span style="font-weight:700;font-size:0.95rem;">
                    <?= date('d M Y', strtotime($v['visit_date'])) ?>
                  </span>
                  <?php if ($v['notes']): ?>
                    <span style="color:var(--text-muted);font-size:0.82rem;margin-left:0.5rem;">
                      · <?= e($v['notes']) ?>
                    </span>
                  <?php endif; ?>
                </div>
                <span style="color:#16A34A;font-weight:700;font-family:'Cinzel',serif;">
                  +<?= number_format($v['points_earned']) ?> pts
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Row 2: Deals + Transfer History -->
    <div class="dash-tools">

      <!-- ACTIVE DEALS -->
      <div class="stat-card" data-aos="fade-up">
        <h4>Active Deals</h4>
        <?php if (empty($deals)): ?>
          <p style="font-size:0.88rem;color:var(--text-muted);">No active deals right now. Check back soon!</p>
        <?php else: ?>
          <div style="display:flex;flex-direction:column;gap:0.75rem;">
            <?php foreach ($deals as $deal): ?>
              <div style="display:flex;gap:0.75rem;align-items:flex-start;">
                <i class="fas fa-tag" style="color:var(--flame-orange);margin-top:3px;flex-shrink:0;font-size:0.85rem;"></i>
                <div>
                  <div style="font-weight:700;font-size:0.9rem;"><?= e($deal['title']) ?></div>
                  <div style="font-size:0.82rem;color:var(--text-muted);"><?= e($deal['description']) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- TRANSFER SUMMARY (last 30 days) -->
      <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
        <h4>
          Transfer Summary
          <span style="font-size:0.72rem;color:var(--text-muted);font-weight:400;text-transform:none;letter-spacing:0;">(last 30 days)</span>
        </h4>
        <?php if (empty($transferHistory)): ?>
          <p style="font-size:0.88rem;color:var(--text-muted);">No transactions in the last 30 days.</p>
        <?php else: ?>
          <div style="border:1px solid var(--border-light);border-radius:var(--radius-sm);overflow:hidden;">
            <?php foreach ($transferHistory as $t):
              $isPositive = in_array($t['type'], ['earned', 'adjusted']);
              $color      = $isPositive ? '#16A34A' : '#DC2626';
              $sign       = $isPositive ? '+' : '−';
            ?>
              <div style="display:flex;justify-content:space-between;padding:0.7rem 1rem;font-size:0.87rem;border-bottom:1px solid var(--border-light);background:white;">
                <span>
                  <?= date('d M', strtotime($t['date'])) ?>
                  · <?= e($t['description'] ?: ucfirst($t['type'])) ?>
                </span>
                <span style="color:<?= $color ?>;font-weight:700;">
                  <?= $sign ?><?= number_format($t['points']) ?> pts
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<!-- ============================================================
     TRANSFER MODAL
     ============================================================ -->
<div class="modal-overlay" id="transferModal">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-title">Transfer Points</div>
      <button class="modal-close" onclick="closeModal('transferModal')">&times;</button>
    </div>
    <p style="font-size:0.88rem;color:var(--text-muted);margin-bottom:1.5rem;line-height:1.7;">
      You have <strong style="color:var(--flame-orange);">
        <?= number_format($customer['total_points']) ?> points
      </strong> available to transfer.
    </p>
    <form method="POST" action="/qoyla/dashboard/index.php">
      <input type="hidden" name="action" value="transfer">
      <div class="form-group">
        <label class="form-label">Points to Transfer</label>
        <input type="number" name="points" class="form-input"
               placeholder="e.g. 500"
               min="1" max="<?= $customer['total_points'] ?>"
               required>
        <div class="form-hint">Minimum 1 point. Maximum <?= number_format($customer['total_points']) ?> points.</div>
      </div>
      <div class="form-group">
        <label class="form-label">Transfer To</label>
        <select name="transfer_type" class="form-select" id="transferTypeSelect"
                style="appearance:none;" onchange="toggleRecipient(this.value)">
          <option value="admin">Qoyla Admin — Discount Redemption</option>
          <option value="hotel">Transfer to Hotel</option>
          <option value="customer">Transfer to Another Customer</option>
        </select>
      </div>
      <div class="form-group" id="recipientField" style="display:none;">
        <label class="form-label">Recipient Phone Number</label>
        <input type="tel" name="recipient_phone" class="form-input"
               placeholder="Customer's phone number">
      </div>
      <button type="submit" class="btn-qoyla"
              style="width:100%;justify-content:center;margin-top:0.5rem;">
        Confirm Transfer <i class="fas fa-check"></i>
      </button>
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
</form>
  </div>
</div>

<script src="/qoyla/assets/js/main.js"></script>
<script>
function toggleRecipient(val) {
  document.getElementById('recipientField').style.display =
    val === 'customer' ? 'block' : 'none';
}
</script>
</body>
</html>
