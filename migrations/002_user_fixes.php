<?php
// ============================================================
// QOYLA — MIGRATION 002
// ============================================================
session_start();
require_once __DIR__ . '/../includes/db.php';

// Safe gate: Only allow admins to run this
require_once __DIR__ . '/../includes/admin_check.php';

echo "<h2>Running Database Migration: 002 User Fixes</h2>";

// 1. Create message_replies table
try {
    $sql1 = "CREATE TABLE IF NOT EXISTS message_replies (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        message_id INT, 
        reply_text TEXT, 
        replied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
        FOREIGN KEY (message_id) REFERENCES contact_messages(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql1);
    echo "<p style='color:green'>✅ SUCCESS: Table 'message_replies' created or already exists.</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ FAILED to create 'message_replies': " . $e->getMessage() . "</p>";
}

// 2. Create points_log table
try {
    $sql2 = "CREATE TABLE IF NOT EXISTS points_log (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        customer_id INT, 
        change_amount INT, 
        reason VARCHAR(255), 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
        expires_at DATE NULL
    )";
    $pdo->exec($sql2);
    echo "<p style='color:green'>✅ SUCCESS: Table 'points_log' created or already exists.</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ FAILED to create 'points_log': " . $e->getMessage() . "</p>";
}

// 3. Alter workers table to add is_active
try {
    // Check if column already exists
    $cols = $pdo->query("SHOW COLUMNS FROM workers LIKE 'is_active'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE workers ADD COLUMN is_active TINYINT DEFAULT 1");
        echo "<p style='color:green'>✅ SUCCESS: Added 'is_active' column to workers table.</p>";
    } else {
        echo "<p style='color:blue'>ℹ️ INFO: 'is_active' column already exists in workers table.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ FAILED to alter 'workers': " . $e->getMessage() . "</p>";
}

echo "<hr/>";
echo "<h3>⚠️ IMPORTANT REMINDER ⚠️</h3>";
echo "<p style='color:red; font-weight:bold;'>IMPORTANT: Delete this file from your server immediately after this run.</p>";
?>
