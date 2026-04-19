<?php
/**
 * Qoyla Loyalty Points Expiration Cron Script
 * -------------------------------------------
 * This script runs routinely (e.g. daily via true server CRON or web-hook)
 * to find any expired points in `points_log`, deduct them from `customers.total_points`,
 * and record the expiration in `points_history` for accurate ledger views.
 */

// If invoked via web, it should be protected by a secret key.
// But as per plan, we just build the logic first.
require_once dirname(__DIR__) . '/includes/db.php';

// Safe execution wrapper
try {
    $pdo->beginTransaction();

    // 1. Find all expired point logs that haven't been marked expired
    // and where change_amount > 0 (points earned, not spent)
    $stmt = $pdo->query("
        SELECT id, customer_id, change_amount, reason 
        FROM points_log 
        WHERE expires_at IS NOT NULL 
          AND expires_at < CURDATE() 
          AND is_expired = 0 
          AND change_amount > 0
    ");
    $expiredLogs = $stmt->fetchAll();

    $expiredCount = 0;
    $totalDeducted = 0;

    foreach ($expiredLogs as $log) {
        $cid = $log['customer_id'];
        $pts = $log['change_amount'];

        // Deduct from customer total_points (ensure it doesn't drop below 0)
        $pdo->prepare("UPDATE customers SET total_points = GREATEST(0, total_points - ?) WHERE id = ?")
            ->execute([$pts, $cid]);

        // Mark as expired in points_log
        $pdo->prepare("UPDATE points_log SET is_expired = 1 WHERE id = ?")
            ->execute([$log['id']]);

        // Record the expiration deduction as a new ledger entry so the user sees it happened
        $reason = "Expired: " . $log['reason'];
        $pdo->prepare("INSERT INTO points_log (customer_id, change_amount, reason, expires_at) VALUES (?, ?, ?, NULL)")
            ->execute([$cid, -$pts, $reason]);

        // Optionally record in points_history as well to keep legacy views accurate
        $pdo->prepare("INSERT INTO points_history (customer_id, points, type, description, date) VALUES (?, ?, 'adjusted', ?, CURDATE())")
            ->execute([$cid, $pts, "Points Expired"]);

        $expiredCount++;
        $totalDeducted += $pts;
    }

    $pdo->commit();
    echo "SUCCESS: Processed {$expiredCount} expired logs. Total points expired: {$totalDeducted}.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR: " . $e->getMessage() . "\n";
}
