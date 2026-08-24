<?php
define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'second_hand_phones');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

function getPDO(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    // Bootstrap: create DB + seed tables if they don't exist yet
    try {
        $bootstrap = new PDO(
            "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET,
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $bootstrap->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $tables = $bootstrap->query(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "' AND table_name = 'users'"
        )->fetchColumn();

        if ((int)$tables === 0) {
            $bootstrap->exec("USE `" . DB_NAME . "`");
            $sql = file_get_contents(__DIR__ . '/../db/init_db.sql');
            $bootstrap->exec($sql);
        }
        
        // Ensure store_settings table exists
        $bootstrap->exec("USE `" . DB_NAME . "`");
        $bootstrap->exec("CREATE TABLE IF NOT EXISTS store_settings (
            setting_key VARCHAR(50) PRIMARY KEY,
            setting_value TEXT NOT NULL
        )");
    } catch (PDOException $e) {
        http_response_code(500);
        die(json_encode(['error' => 'DB bootstrap failed: ' . $e->getMessage()]));
    }

    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        http_response_code(500);
        die(json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]));
    }

    return $pdo;
}

/**
 * Retrieve a global store setting value from DB
 */
function getStoreSetting(string $key, string $default = ''): string {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT setting_value FROM store_settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return ($val !== false && $val !== null && $val !== '') ? (string)$val : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Save a global store setting value into DB
 */
function setStoreSetting(string $key, string $value): bool {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("INSERT INTO store_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        return $stmt->execute([$key, $value, $value]);
    } catch (Exception $e) {
        return false;
    }
}
