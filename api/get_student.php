<?php
/* ============================================================
   API: Get Single Student Record for Editing
   ============================================================ */

include __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid student ID.']);
    exit;
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT id, name, course FROM students WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        echo json_encode($student);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Student record not found.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
