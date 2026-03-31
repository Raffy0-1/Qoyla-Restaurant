<?php
// Quick migration: add image_path to deals table
require_once __DIR__ . '/includes/db.php';

try {
    // Check if column already exists
    $cols = $pdo->query("SHOW COLUMNS FROM deals LIKE 'image_path'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE deals ADD COLUMN image_path VARCHAR(255) DEFAULT NULL");
        echo "✅ Added image_path column to deals table.\n";
    } else {
        echo "ℹ️ image_path column already exists in deals table.\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
