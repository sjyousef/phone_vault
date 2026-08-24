<?php
/* ============================================================
   API: Update Existing Phone Record (Prepared Statement)
   ============================================================ */

include __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents("php://input"), true);

$id             = (int)($data['id'] ?? 0);
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

if ($id <= 0 || $brand === '' || $model === '' || $imei === '' || $sellingPrice <= 0) {
    http_response_code(400);
    echo json_encode([
        "status"  => "error",
        "message" => "Valid ID, brand, model, IMEI, and selling price are required."
    ]);
    exit;
}

try {
    $pdo = getPDO();

    // Check duplicate IMEI for other records
    $checkStmt = $pdo->prepare("SELECT id FROM phones WHERE imei = ? AND id != ? LIMIT 1");
    $checkStmt->execute([$imei, $id]);
    if ($checkStmt->fetch()) {
        http_response_code(400);
        echo json_encode([
            "status"  => "error",
            "message" => "Another phone with this IMEI already exists."
        ]);
        exit;
    }

    $stmt = $pdo->prepare(
        "UPDATE phones 
         SET brand = ?, model = ?, imei = ?, storage = ?, color = ?, battery_health = ?, condition_grade = ?, cost_price = ?, selling_price = ?, status = ?
         WHERE id = ?"
    );

    $success = $stmt->execute([
        $brand, $model, $imei, $storage, $color,
        $batteryHealth, $conditionGrade, $costPrice, $sellingPrice, $status, $id
    ]);

    if ($success) {
        echo json_encode([
            "status"  => "success",
            "message" => "Phone record updated successfully."
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "status"  => "error",
            "message" => "Unable to update phone record."
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
