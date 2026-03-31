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
?>