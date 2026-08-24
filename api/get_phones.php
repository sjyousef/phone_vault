<?php
/* ============================================================
   API: Get All Phones Records
   ============================================================ */

include __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT id, brand, model, imei, storage, color, battery_health, condition_grade, cost_price, selling_price, status, DATE_FORMAT(created_at, '%Y-%m-%d') AS created_at FROM phones ORDER BY id DESC");
    $phones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($phones);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Unable to fetch records: ' . $e->getMessage()]);
}
