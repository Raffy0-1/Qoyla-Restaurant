<?php
require_once __DIR__ . '/includes/db.php';
header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM deals WHERE id = ? AND is_active = 1");
$stmt->execute([$id]);
$deal = $stmt->fetch(PDO::FETCH_ASSOC);

if ($deal) {
    echo json_encode($deal);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Deal not found']);
}
