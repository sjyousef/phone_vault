<?php
/* ============================================================
   API: Delete Phone Record (Prepared Statement)
   ============================================================ */

include __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents("php://input"), true);

$id = (int)($data['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode([
        "status"  => "error",
        "message" => "Valid phone ID is required."
    ]);
    exit;
}

try {
    $pdo = getPDO();

    // Check if phone has sales attached
    $checkSales = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE phone_id = ?");
    $checkSales->execute([$id]);
    if ((int)$checkSales->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode([
            "status"  => "error",
            "message" => "Cannot delete phone because sales records exist for this unit."
        ]);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM phones WHERE id = ?");
    $success = $stmt->execute([$id]);

    if ($success) {
        echo json_encode([
            "status"  => "success",
            "message" => "Phone record deleted successfully."
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "status"  => "error",
            "message" => "Unable to delete phone record."
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
