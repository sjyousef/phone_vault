<?php
require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    header('Location: /Second_Hand_Phone_Store/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/config/database.php';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = getPDO()->prepare('SELECT id, full_name, username, password_hash, role FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['username']  = $user['username'];
            header('Location: /Second_Hand_Phone_Store/index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
$pageTitle = 'Login';
$pageStyle = 'login.css';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | PhoneVault</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/Second_Hand_Phone_Store/assets/css/custom.css">
</head>
<body>
<div class="pv-login-wrap">
    <div class="pv-login-card">
        <div class="pv-login-logo">
            <i class="fa-solid fa-mobile-screen-button"></i>
            <h1>PhoneVault</h1>
            <p>Second-Hand Phone Store & Warranty System</p>
        </div>

        <?php if ($error): ?>
        <div class="pv-alert pv-alert-danger">
            <i class="fa-solid fa-circle-xmark"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label fw-600">Username</label>
                <div class="input-group">
                    <span class="input-group-text" style="background:var(--pv-surface-2);border-color:var(--pv-border);color:var(--pv-text-muted)">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    <input type="text" class="form-control pv-input" name="username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           placeholder="Enter username" autofocus required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-600">Password</label>
                <div class="input-group">
                    <span class="input-group-text" style="background:var(--pv-surface-2);border-color:var(--pv-border);color:var(--pv-text-muted)">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input type="password" class="form-control pv-input" name="password" id="loginPassword"
                           placeholder="Enter password" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePasswordBtn" style="border-color:var(--pv-border);color:var(--pv-text-muted)">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="pv-btn-primary w-100 justify-content-center py-2">
                <i class="fa-solid fa-right-to-bracket"></i> Sign In
            </button>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Second_Hand_Phone_Store/assets/js/app.js"></script>
<script src="/Second_Hand_Phone_Store/assets/js/login.js"></script>
</body>
</html>
