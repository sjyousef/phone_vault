<?php
/* ============================================================
   PhoneVault – Global Config & Database Bootstrap
   ============================================================ */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

// Ensure database connection
$pdo = getPDO();

// Create students table automatically if it does not exist
$pdo->exec("CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    course VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Seed initial student records if empty
$count = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
if ((int)$count === 0) {
    $stmt = $pdo->prepare("INSERT INTO students (name, course) VALUES (?, ?)");
    $stmt->execute(['Alex Mercer', 'BS Information Technology']);
    $stmt->execute(['Sophia Chen', 'BS Computer Science']);
    $stmt->execute(['Liam Johnson', 'BS Cybersecurity']);
}
