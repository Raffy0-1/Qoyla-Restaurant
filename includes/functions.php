<?php
// ============================================================
// QOYLA — HELPER FUNCTIONS
// ============================================================

// Safely display any text on screen
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Set a flash message to show on next page load
function setFlash($type, $message) {
    $_SESSION['flash_type']    = $type;   // 'success' or 'error'
    $_SESSION['flash_message'] = $message;
}

// Get and clear the flash message (call once at top of page)
function getFlash() {
    if (!isset($_SESSION['flash_message'])) return '';
    $type = $_SESSION['flash_type'];
    $msg  = $_SESSION['flash_message'];
    unset($_SESSION['flash_type'], $_SESSION['flash_message']);
    $icon = ($type === 'success') ? 'fa-check-circle' : 'fa-exclamation-circle';
    return "<div class='flash flash-{$type}'>
                <i class='fas {$icon}'></i> {$msg}
            </div>";
}

// Auto-calculate inventory status based on quantity vs levels
function inventoryStatus($qty, $par, $reorder) {
    if ($qty <= 0)        return ['out_of_stock', 'Out of Stock', 'status-red'];
    if ($qty <= $reorder) return ['low',           'Low Stock',   'status-yellow'];
    return                       ['in_stock',      'In Stock',    'status-green'];
}

// Calculate caution badge for workers
function cautionBadge($level) {
    $map = [
        0 => ['status-green',  'Clear ✓'],
        1 => ['status-yellow', 'Caution ⚠'],
        2 => ['status-orange', 'Warning ⛔'],
        3 => ['status-red',    '3 Complaints 🔴'],
    ];
    $l = min($level, 3);
    return "<span class='status {$map[$l][0]}'>{$map[$l][1]}</span>";
}

// Format points display
function fmtPoints($pts) {
    return number_format($pts) . ' pts';
}

// Loyalty points to rupees value
define('POINT_VALUE', 0.143);   // 1 point = Rs. 0.143
function pointsToRs($pts) {
    return 'Rs. ' . number_format($pts * POINT_VALUE, 0);
}

// ============================================================
// CSRF PROTECTION
// ============================================================

// Generate and retrieve CSRF token
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify submitted CSRF token
function verifyCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        die('CSRF token validation failed. Please refresh the page and try again.');
    }
    return true;
}

// ============================================================
// EMAIL HELPER
// ============================================================
function sendQoylaEmail($to, $subject, $bodyHTML) {
    $message = "
    <html>
    <head>
      <title>$subject</title>
      <link href='https://fonts.googleapis.com/css2?family=Cinzel:wght@700&display=swap' rel='stylesheet'>
      <style>
        body { font-family: Arial, sans-serif; background-color: #1A1A1A; color: #FFFFFF; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background-color: #2D2D2D; padding: 30px; border-radius: 8px; border-top: 4px solid #E8500A; }
        .header { text-align: center; margin-bottom: 20px; font-size: 24px; font-family: 'Cinzel', serif; font-weight: bold; color: #E8500A; letter-spacing: 2px; }
        .content { font-size: 16px; line-height: 1.6; color: #E5E5E5; }
        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #888888; border-top: 1px solid #444; padding-top: 15px; }
      </style>
    </head>
    <body>
      <div class='container'>
        <div class='header'>QOYLA RESTAURANT</div>
        <div class='content'>
          $bodyHTML
        </div>
        <div class='footer'>
          &copy; " . date('Y') . " Qoyla Restaurant, Multan. Authentic desi flavors, crafted over charcoal.<br>
          <a href='https://qoyla.pk' style='color:#E8500A; text-decoration:none;'>Visit our website</a>
        </div>
      </div>
    </body>
    </html>
    ";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Qoyla Restaurant <noreply@qoyla.pk>\r\n";
    $headers .= "Reply-To: info@qoyla.pk\r\n";

    return @mail($to, $subject, $message, $headers);
}
?>