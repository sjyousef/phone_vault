<?php
/* ============================================================
   API: Add New Student Record (Prepared Statement)
   ============================================================ */

include __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents("php://input"), true);

$name   = trim($data['name'] ?? '');
$course = trim($data['course'] ?? '');

if ($name === '' || $course === '') {
    http_response_code(400);
    echo json_encode([
        "status"  => "error",
        "message" => "Name and course are required."
    ]);
    exit;
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("INSERT INTO students (name, course) VALUES (?, ?)");
    $success = $stmt->execute([$name, $course]);

    if ($success) {
        echo json_encode([
            "status"  => "success",
            "message" => "Student added successfully."
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "status"  => "error",
            "message" => "Unable to add student."
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
