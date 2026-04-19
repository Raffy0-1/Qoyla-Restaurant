<?php
require_once __DIR__ . '/includes/db.php';
header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ? AND is_available = 1");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if ($item) {
    echo json_encode($item);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Item not found']);
}
