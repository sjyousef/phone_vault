<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /Second_Hand_Phone_Store/login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        header('Location: /Second_Hand_Phone_Store/index.php?error=unauthorized');
        exit;
    }
}

function currentUser(): array {
    return [
        'id'       => $_SESSION['user_id']   ?? null,
        'name'     => $_SESSION['user_name'] ?? '',
        'role'     => $_SESSION['user_role'] ?? '',
        'username' => $_SESSION['username']  ?? '',
    ];
}

function isAdmin(): bool {
    return ($_SESSION['user_role'] ?? '') === 'admin';
}
