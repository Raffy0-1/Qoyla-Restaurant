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

// Points history last 30 days (legacy)
$stmt = $pdo->prepare("
    SELECT * FROM points_history
    WHERE customer_id = ?
      AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ORDER BY date DESC
");
$stmt->execute([$userId]);
$transferHistory = $stmt->fetchAll();

// Fetch fully detailed Points Ledger
$stmt = $pdo->prepare("
    SELECT * FROM points_log
    WHERE customer_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$userId]);
$pointsLedger = $stmt->fetchAll();

// Fetch My Messages
$stmt = $pdo->prepare("
    SELECT cm.*, mr.reply_text, mr.replied_at 
    FROM contact_messages cm 
    LEFT JOIN message_replies mr ON cm.id = mr.message_id 
    WHERE cm.phone = ? OR cm.email = ? 
    ORDER BY cm.submitted_at DESC
");
$stmt->execute([$customer['phone'], $customer['email']]);
$myMessages = $stmt->fetchAll();


// Active deals
$deals = $pdo->query("SELECT * FROM deals WHERE is_active = 1 ORDER BY id DESC")->fetchAll();

// Handle Transfer Points form submission
$transferError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'transfer') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    
    $points           = (int)($_POST['points'] ?? 0);
    $transferType     = $_POST['transfer_type'] ?? 'admin';
    $recipientPhone   = trim($_POST['recipient_phone'] ?? '');

    if ($points <= 0) {
        $transferError = 'Please enter a valid number of points.';
    } elseif ($points > $customer['total_points']) {
        $transferError = 'You don\'t have enough points.';
    } elseif ($transferType === 'customer' && empty($recipientPhone)) {
        $transferError = 'Please enter the recipient\'s phone number.';
    } else {

        // For customer transfers: look up recipient first (before opening transaction)
        $recipient = null;
        if ($transferType === 'customer') {
            $rStmt = $pdo->prepare("SELECT id, name, phone FROM customers WHERE phone = ? LIMIT 1");
            $rStmt->execute([$recipientPhone]);
            $recipient = $rStmt->fetch();

            if (!$recipient) {
                $transferError = 'No customer found with phone number "' . htmlspecialchars($recipientPhone, ENT_QUOTES, 'UTF-8') . '". Please check and try again.';
            } elseif ($recipient['id'] === $userId) {
                $transferError = 'You cannot transfer points to yourself.';
            }
        }

        if (!$transferError) {
            try {
                $pdo->beginTransaction();

                // 1. Deduct points from sender
                $pdo->prepare("UPDATE customers SET total_points = total_points - ? WHERE id = ?")
                    ->execute([$points, $userId]);

                // 2. Log sender's deduction in history and points_log
                $desc = match ($transferType) {
                    'customer' => 'Transferred to ' . ($recipient['name'] ?? 'Customer'),
                    'hotel'    => 'Transferred to Hotel',
                    default    => 'Transferred to Admin (Discount Redemption)',
                };
                $pdo->prepare("
                    INSERT INTO points_history (customer_id, points, type, description, date)
                    VALUES (?, ?, 'transferred', ?, CURDATE())
                ")->execute([$userId, $points, $desc]);
                
                $pdo->prepare("
                    INSERT INTO points_log (customer_id, change_amount, reason, expires_at)
                    VALUES (?, ?, ?, NULL)
                ")->execute([$userId, -$points, $desc]);

                // 3. If customer transfer: credit recipient & log their history
                if ($transferType === 'customer' && $recipient) {
                    $pdo->prepare("UPDATE customers SET total_points = total_points + ? WHERE id = ?")
                        ->execute([$points, $recipient['id']]);

                    $rcvDesc = 'Received from ' . ($customer['name'] ?? 'Member #' . $customer['sr_no']);
                    $pdo->prepare("
                        INSERT INTO points_history (customer_id, points, type, description, date)
                        VALUES (?, ?, 'adjusted', ?, CURDATE())
                    ")->execute([
                        $recipient['id'],
                        $points,
                        $rcvDesc
                    ]);
                    
                    $pdo->prepare("
                        INSERT INTO points_log (customer_id, change_amount, reason, expires_at)
                        VALUES (?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 6 MONTH))
                    ")->execute([
                        $recipient['id'],
                        $points,
                        $rcvDesc
                    ]);
                }

                $pdo->commit();

                // Refresh sender's data
                $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
                $stmt->execute([$userId]);
                $customer = $stmt->fetch();

                $successMsg = $points . ' points transferred successfully!';
                if ($transferType === 'customer' && $recipient) {
                    $successMsg .= ' ' . htmlspecialchars($recipient['name'], ENT_QUOTES, 'UTF-8') . ' has received the points.';
                }
                setFlash('success', $successMsg);
                header('Location: /qoyla/dashboard/index.php');
                exit;
            } catch (\Exception $e) {
                $pdo->rollBack();
                $transferError = 'An error occurred during transfer. Please try again.';
            }
        }
    }
}

// Handle Change Password form submission
$passwordError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    
    $currentPass = $_POST['current_password'] ?? '';
    $newPass     = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
        $passwordError = 'Please fill in all password fields.';
    } elseif (!password_verify($currentPass, $customer['password'])) {
        $passwordError = 'Current password is incorrect.';
    } elseif (strlen($newPass) < 6) {
        $passwordError = 'New password must be at least 6 characters.';
    } elseif ($newPass !== $confirmPass) {
        $passwordError = 'New passwords do not match.';
    } else {
        $hashedPass = password_hash($newPass, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE customers SET password = ? WHERE id = ?")
            ->execute([$hashedPass, $userId]);
        setFlash('success', 'Password changed successfully!');
        header('Location: /qoyla/dashboard/index.php');
        exit;
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
    
    <!-- Row 3: Points Ledger & Password -->
    <div class="dash-layout" style="margin-top:2.5rem;">
      <!-- FULL POINTS LEDGER -->
      <div class="stat-card" data-aos="fade-up">
        <h4>Points Ledger 
           <span style="font-size:0.72rem;color:var(--text-muted);font-weight:400;text-transform:none;letter-spacing:0;">(Full History)</span>
        </h4>
        <?php if (empty($pointsLedger)): ?>
          <p style="font-size:0.88rem;color:var(--text-muted);">No points logged yet.</p>
        <?php else: ?>
          <div style="border:1px solid var(--border-light);border-radius:var(--radius-sm);overflow:hidden;max-height:400px;overflow-y:auto;">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Reason</th>
                  <th>Amount</th>
                  <th>Expiry Date</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pointsLedger as $pl):
                  $isPositive = $pl['change_amount'] > 0;
                  $color      = $isPositive ? '#16A34A' : '#DC2626';
                  $sign       = $isPositive ? '+' : '';
                  // Highlight expiry < 30 days
                  $expiresStyle = '';
                  if ($pl['expires_at']) {
                      $daysLeft = (strtotime($pl['expires_at']) - time()) / (60*60*24);
                      if ($daysLeft > 0 && $daysLeft <= 30) {
                          $expiresStyle = 'color:var(--flame-orange);font-weight:bold;';
                      }
                  }
                ?>
                  <tr style="background:white;border-bottom:1px solid var(--border-light);">
                    <td style="font-size:0.87rem;"><?= date('d M Y', strtotime($pl['created_at'])) ?></td>
                    <td style="font-size:0.87rem;"><?= e($pl['reason']) ?></td>
                    <td style="font-weight:700;color:<?= $color ?>;font-size:0.87rem;">
                      <?= $sign ?><?= number_format($pl['change_amount']) ?> pts
                    </td>
                    <td style="font-size:0.87rem;<?= $expiresStyle ?>">
                      <?= $pl['expires_at'] ? date('d M Y', strtotime($pl['expires_at'])) : '—' ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <!-- CHANGE PASSWORD -->
      <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
        <h4>Change Password</h4>
        <?php if (!empty($passwordError)): ?>
          <div class="flash flash-error" style="margin-bottom:1rem;position:relative;animation:none;">
            <i class="fas fa-exclamation-circle"></i> <?= e($passwordError) ?>
          </div>
        <?php endif; ?>
        <form method="POST" action="/qoyla/dashboard/index.php" data-loading>
          <input type="hidden" name="action" value="change_password">
          <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
          
          <div class="form-group">
            <label class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-input" placeholder="Min 6 characters" required>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-input" required>
          </div>
          <button type="submit" class="btn-qoyla-outline" style="width:100%;justify-content:center;margin-top:0.5rem;">
            Update Password
          </button>
        </form>
      </div>
    </div>

    <!-- Row 4: My Messages -->
    <div class="dash-layout" style="margin-top:2.5rem;grid-template-columns:1fr;">
      <div class="stat-card" data-aos="fade-up">
        <h4>My Messages & Complaints</h4>
        <?php if (empty($myMessages)): ?>
          <p style="font-size:0.88rem;color:var(--text-muted);">You have not sent any messages or complaints.</p>
        <?php else: ?>
          <div style="display:flex;flex-direction:column;gap:1.5rem;">
            <?php foreach ($myMessages as $msg): ?>
              <div style="border:1px solid var(--border-light);border-left:4px solid var(--flame-orange);border-radius:var(--radius-sm);background:white;padding:1.5rem;">
                <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border-light);padding-bottom:0.75rem;margin-bottom:0.75rem;">
                  <span style="font-size:0.8rem;text-transform:uppercase;color:var(--text-muted);font-weight:700;letter-spacing:1px;">
                    <?= $msg['form_type'] === 'complaint' ? '<i class="fas fa-exclamation-triangle" style="color:var(--flame-orange)"></i> Complaint' : '<i class="fas fa-comment" style="color:var(--text-muted)"></i> Inquiry' ?>
                  </span>
                  <span style="font-size:0.8rem;color:var(--text-muted);">
                    <?= date('d M Y, h:i A', strtotime($msg['created_at'])) ?>
                  </span>
                </div>
                <div style="font-size:0.95rem;line-height:1.6;color:var(--text-body);">
                  <?= nl2br(e($msg['message'])) ?>
                </div>
                
                <?php if ($msg['reply_text']): ?>
                  <div style="margin-top:1.5rem;padding:1rem;background:var(--off-white);border-radius:var(--radius-sm);">
                    <div style="font-size:0.8rem;color:var(--flame-orange);text-transform:uppercase;font-weight:700;letter-spacing:1px;margin-bottom:0.5rem;">
                      <i class="fas fa-reply"></i> Admin Reply
                    </div>
                    <div style="font-size:0.9rem;line-height:1.5;color:var(--charcoal-black);">
                      <?= nl2br(e($msg['reply_text'])) ?>
                    </div>
                    <div style="font-size:0.75rem;color:var(--text-muted);margin-top:0.5rem;">
                      Replies at <?= date('d M Y, h:i A', strtotime($msg['replied_at'])) ?>
                    </div>
                  </div>
                <?php endif; ?>
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
    <form method="POST" action="/qoyla/dashboard/index.php" data-loading>
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
