<?php
/* ============================================================
   API: Get All Students Records
   ============================================================ */

include __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT id, name, course, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') AS created_at FROM students ORDER BY id DESC");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($students);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Unable to fetch records: ' . $e->getMessage()]);
}
