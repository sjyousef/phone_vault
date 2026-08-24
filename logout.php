<?php
require_once __DIR__ . '/config/auth.php';

// Unset all session variables
$_SESSION = array();

// If a session cookie exists, destroy it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// Redirect to login page
header('Location: /Second_Hand_Phone_Store/login.php');
exit;
