<?php
/* ============================================================
   API: Update Existing Student Record (Prepared Statement)
   ============================================================ */

include __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents("php://input"), true);

$id     = (int)($data['id'] ?? 0);
$name   = trim($data['name'] ?? '');
$course = trim($data['course'] ?? '');

if ($id <= 0 || $name === '' || $course === '') {
    http_response_code(400);
    echo json_encode([
        "status"  => "error",
        "message" => "Valid ID, name, and course are required."
    ]);
    exit;
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("UPDATE students SET name = ?, course = ? WHERE id = ?");
    $success = $stmt->execute([$name, $course, $id]);

    if ($success) {
        echo json_encode([
            "status"  => "success",
            "message" => "Student updated successfully."
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "status"  => "error",
            "message" => "Unable to update student record."
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
