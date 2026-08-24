<?php
/* ============================================================
   API: Add New Phone Record (Prepared Statement)
   ============================================================ */

include __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents("php://input"), true);

$brand          = trim($data['brand'] ?? '');
$model          = trim($data['model'] ?? '');
$imei           = trim($data['imei'] ?? '');
$storage        = trim($data['storage'] ?? '128GB');
$color          = trim($data['color'] ?? 'Black');
$batteryHealth  = (int)($data['battery_health'] ?? 100);
$conditionGrade = trim($data['condition_grade'] ?? 'Grade A');
$costPrice      = (float)($data['cost_price'] ?? 0);
$sellingPrice   = (float)($data['selling_price'] ?? 0);
$status         = trim($data['status'] ?? 'Available');

if ($brand === '' || $model === '' || $imei === '' || $sellingPrice <= 0) {
    http_response_code(400);
    echo json_encode([
        "status"  => "error",
        "message" => "Brand, model, IMEI, and valid selling price are required."
    ]);
    exit;
}

try {
    $pdo = getPDO();

    // Check duplicate IMEI
    $checkStmt = $pdo->prepare("SELECT id FROM phones WHERE imei = ? LIMIT 1");
    $checkStmt->execute([$imei]);
    if ($checkStmt->fetch()) {
        http_response_code(400);
        echo json_encode([
            "status"  => "error",
            "message" => "A phone with this IMEI already exists."
        ]);
        exit;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO phones (brand, model, imei, storage, color, battery_health, condition_grade, cost_price, selling_price, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $success = $stmt->execute([
        $brand, $model, $imei, $storage, $color,
        $batteryHealth, $conditionGrade, $costPrice, $sellingPrice, $status
    ]);

    if ($success) {
        echo json_encode([
            "status"  => "success",
            "message" => "Phone record added successfully."
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "status"  => "error",
            "message" => "Unable to add phone record."
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
