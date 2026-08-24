<?php
/* ============================================================
   API: Get Single Phone Record for Editing
   ============================================================ */

include __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid phone ID.']);
    exit;
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT id, brand, model, imei, storage, color, battery_health, condition_grade, cost_price, selling_price, status FROM phones WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $phone = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($phone) {
        echo json_encode($phone);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Phone record not found.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
