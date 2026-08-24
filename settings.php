<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
requireLogin();

$pdo = getPDO();
$pageTitle = 'Settings';
$pageScript = 'settings.js';
$pageStyle  = 'settings.css';
$user = currentUser();
$message = '';
$msgType = '';

// Ensure store_settings table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS store_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT NOT NULL
)");

// Default settings values helper
function getSetting(PDO $pdo, string $key, string $default = ''): string {
    $stmt = $pdo->prepare("SELECT setting_value FROM store_settings WHERE setting_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return $val !== false ? $val : $default;
}

function setSetting(PDO $pdo, string $key, string $value): void {
    $stmt = $pdo->prepare("INSERT INTO store_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$key, $value, $value]);
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_store_settings') {
        setStoreSetting('store_name', trim($_POST['store_name'] ?? 'PhoneVault Store'));
        setStoreSetting('store_phone', trim($_POST['store_phone'] ?? ''));
        setStoreSetting('currency_symbol', trim($_POST['currency_symbol'] ?? '₱'));
        setStoreSetting('default_warranty_days', (string)(int)($_POST['default_warranty_days'] ?? 30));
        setStoreSetting('low_battery_threshold', (string)(int)($_POST['low_battery_threshold'] ?? 75));
        
        $message = 'Store settings saved successfully.';
        $msgType = 'success';
    } elseif ($action === 'change_password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass     = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (!$currentPass || !$newPass || !$confirmPass) {
            $message = 'Please fill in all password fields.';
            $msgType = 'danger';
        } elseif ($newPass !== $confirmPass) {
            $message = 'New passwords do not match.';
            $msgType = 'danger';
        } elseif (strlen($newPass) < 6) {
            $message = 'New password must be at least 6 characters long.';
            $msgType = 'danger';
        } else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$user['id']]);
            $hash = $stmt->fetchColumn();

            if ($hash && password_verify($currentPass, $hash)) {
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $user['id']]);
                $message = 'Password updated successfully!';
                $msgType = 'success';
            } else {
                $message = 'Current password is incorrect.';
                $msgType = 'danger';
            }
        }
    } elseif ($action === 'add_user' && isAdmin()) {
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 'staff';

        if (!$fullName || !$username || !$password) {
            $message = 'Please fill in all required fields for the new user.';
            $msgType = 'danger';
        } else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, username, password_hash, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$fullName, $username, $hash, $role]);
                $message = "User <strong>{$username}</strong> added successfully.";
                $msgType = 'success';
            } catch (PDOException $e) {
                $message = $e->getCode() === '23000' ? 'Username already exists.' : 'Could not add user.';
                $msgType = 'danger';
            }
        }
    } elseif ($action === 'delete_user' && isAdmin()) {
        $targetId = (int)($_POST['target_user_id'] ?? 0);
        if ($targetId === (int)$user['id']) {
            $message = 'You cannot delete your own logged in account.';
            $msgType = 'danger';
        } else {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$targetId]);
            $message = 'User account removed.';
            $msgType = 'success';
        }
    }
}

// Fetch settings
$storeName       = getStoreSetting('store_name', 'PhoneVault Store');
$storePhone      = getStoreSetting('store_phone', '+63 917 123 4567');
$currencySymbol  = getStoreSetting('currency_symbol', '₱');
$defaultWarranty = getStoreSetting('default_warranty_days', '30');
$batteryLimit    = getStoreSetting('low_battery_threshold', '75');

// Fetch user accounts
$usersList = $pdo->query("SELECT id, full_name, username, role, created_at FROM users ORDER BY created_at ASC")->fetchAll();

// System Data stats
$totalPhonesCount = $pdo->query("SELECT COUNT(*) FROM phones")->fetchColumn();
$totalSalesCount  = $pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="d-flex">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<main class="pv-main">
    <div class="pv-page-header">
        <div>
            <h1 class="pv-page-title"><i class="fa-solid fa-gear"></i> Settings</h1>
            <div class="pv-page-subtitle">Configure store preferences, manage account security & users</div>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="pv-alert pv-alert-<?= $msgType ?>">
        <i class="fa-solid fa-<?= $msgType === 'success' ? 'circle-check' : 'circle-xmark' ?>"></i>
        <span><?= $message ?></span>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Store Settings & System Preferences -->
        <div class="col-lg-6">
            <div class="pv-card mb-4">
                <div class="pv-card-header">
                    <h2 class="pv-card-title"><i class="fa-solid fa-store"></i> Store Settings</h2>
                </div>
                <div class="pv-card-body">
                    <form method="POST" action="/Second_Hand_Phone_Store/settings.php" id="storeSettingsForm">
                        <input type="hidden" name="action" value="update_store_settings">
                        
                        <div class="mb-3">
                            <label class="form-label fw-600">Store Name</label>
                            <input type="text" class="form-control pv-input" name="store_name" value="<?= htmlspecialchars($storeName) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-600">Contact Phone / Hotline</label>
                            <input type="text" class="form-control pv-input" name="store_phone" value="<?= htmlspecialchars($storePhone) ?>">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-600">Currency Symbol</label>
                                <input type="text" class="form-control pv-input" name="currency_symbol" value="<?= htmlspecialchars($currencySymbol) ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-600">Default Warranty (Days)</label>
                                <input type="number" class="form-control pv-input" name="default_warranty_days" value="<?= htmlspecialchars($defaultWarranty) ?>" min="1" max="365" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-600">Low Battery Alert Threshold (%)</label>
                            <input type="number" class="form-control pv-input" name="low_battery_threshold" value="<?= htmlspecialchars($batteryLimit) ?>" min="1" max="100" required>
                            <div class="form-text">Phones with battery health below this % will trigger warning alerts.</div>
                        </div>

                        <button type="submit" class="pv-btn-primary">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Store Settings
                        </button>
                    </form>
                </div>
            </div>

            <!-- Password Security -->
            <div class="pv-card">
                <div class="pv-card-header">
                    <h2 class="pv-card-title"><i class="fa-solid fa-key"></i> Account Security (Change Password)</h2>
                </div>
                <div class="pv-card-body">
                    <form method="POST" action="/Second_Hand_Phone_Store/settings.php" id="changePasswordForm">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-3">
                            <label class="form-label fw-600">Current Password</label>
                            <input type="password" class="form-control pv-input" name="current_password" required>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-600">New Password</label>
                                <input type="password" class="form-control pv-input" name="new_password" id="newPassword" minlength="6" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-600">Confirm New Password</label>
                                <input type="password" class="form-control pv-input" name="confirm_password" id="confirmPassword" minlength="6" required>
                            </div>
                        </div>

                        <button type="submit" class="pv-btn-primary">
                            <i class="fa-solid fa-shield-halved me-1"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- User Accounts & System Stats -->
        <div class="col-lg-6">
            <?php if (isAdmin()): ?>
            <!-- User Management (Admin Only) -->
            <div class="pv-card mb-4">
                <div class="pv-card-header">
                    <h2 class="pv-card-title"><i class="fa-solid fa-users"></i> System Accounts (Admin Only)</h2>
                    <button class="pv-btn-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#addUserForm">
                        <i class="fa-solid fa-user-plus"></i> Add Account
                    </button>
                </div>
                <div class="pv-card-body">
                    <!-- Add User Form (Collapsible) -->
                    <div class="collapse mb-4" id="addUserForm">
                        <form method="POST" action="/Second_Hand_Phone_Store/settings.php" class="p-3 border rounded bg-surface-2">
                            <input type="hidden" name="action" value="add_user">
                            <h6 class="fw-700 mb-3"><i class="fa-solid fa-user-plus me-1"></i> Create New User Account</h6>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control pv-input" name="full_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control pv-input" name="username" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Password</label>
                                    <input type="password" class="form-control pv-input" name="password" minlength="6" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Role</label>
                                    <select class="form-select pv-input" name="role" required>
                                        <option value="staff">Staff Member</option>
                                        <option value="admin">Administrator</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="pv-btn-primary btn-sm">Create User</button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="pv-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usersList as $u): ?>
                                <tr>
                                    <td>
                                        <div class="fw-700"><?= htmlspecialchars($u['full_name']) ?></div>
                                    </td>
                                    <td class="font-mono"><?= htmlspecialchars($u['username']) ?></td>
                                    <td>
                                        <span class="pv-role-badge"><?= ucfirst($u['role']) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <?php if ((int)$u['id'] !== (int)$user['id']): ?>
                                        <form method="POST" action="/Second_Hand_Phone_Store/settings.php" class="d-inline" onsubmit="return confirm('Delete this user account?');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                                            <button type="submit" class="pv-btn-icon danger" title="Delete User">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">Current</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- System Info & Data Health -->
            <div class="pv-card">
                <div class="pv-card-header">
                    <h2 class="pv-card-title"><i class="fa-solid fa-server"></i> System Diagnostic & Data Stats</h2>
                </div>
                <div class="pv-card-body">
                    <div class="row g-3 text-center mb-3">
                        <div class="col-4">
                            <div class="pv-pos-summary p-2">
                                <div class="pv-stat-value" style="font-size:1.4rem"><?= count($usersList) ?></div>
                                <div class="pv-stat-label">Users</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="pv-pos-summary p-2">
                                <div class="pv-stat-value" style="font-size:1.4rem"><?= $totalPhonesCount ?></div>
                                <div class="pv-stat-label">Phones</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="pv-pos-summary p-2">
                                <div class="pv-stat-value" style="font-size:1.4rem"><?= $totalSalesCount ?></div>
                                <div class="pv-stat-label">Sales</div>
                            </div>
                        </div>
                    </div>

                    <dl class="row mb-0 small text-muted">
                        <dt class="col-5">PHP Version</dt>
                        <dd class="col-7 font-mono"><?= PHP_VERSION ?></dd>
                        <dt class="col-5">Database Engine</dt>
                        <dd class="col-7 font-mono">MySQL (PDO)</dd>
                        <dt class="col-5">App Environment</dt>
                        <dd class="col-7"><span class="badge bg-success">Healthy & Online</span></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</main>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
