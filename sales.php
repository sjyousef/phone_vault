<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
requireLogin();

$pdo = getPDO();

// AJAX: IMEI lookup
if (isset($_GET['ajax']) && $_GET['ajax'] === 'lookup') {
    header('Content-Type: application/json');
    $imei = trim($_GET['imei'] ?? '');
    if (!$imei) { echo json_encode(['found' => false]); exit; }
    $stmt = $pdo->prepare("SELECT * FROM phones WHERE imei = ? AND status = 'Available' LIMIT 1");
    $stmt->execute([$imei]);
    $phone = $stmt->fetch();
    if ($phone) {
        echo json_encode(['found' => true] + $phone);
    } else {
        $exists = $pdo->prepare("SELECT status FROM phones WHERE imei = ? LIMIT 1");
        $exists->execute([$imei]);
        $row = $exists->fetch();
        echo json_encode([
            'found'   => false,
            'imei'    => $imei,
            'message' => $row ? 'Phone is ' . $row['status'] . ' and not available for sale.' : 'No phone found with this IMEI.',
        ]);
    }
    exit;
}

$pageTitle = 'Sales / POS';
$pageScript = 'sales.js';
$pageStyle  = 'sales.css';
$message = '';
$msgType = '';

// Process sale
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phoneId         = (int)$_POST['phone_id'];
    $customerName    = trim($_POST['customer_name']);
    $customerPhone   = trim($_POST['customer_phone']);
    $paymentMethod   = $_POST['payment_method'];
    $warrantyDays    = (int)$_POST['warranty_duration_days'];
    $totalAmount     = (float)$_POST['total_amount'];

    if (!$phoneId || !$customerName || !$totalAmount) {
        $message = 'Please fill in all required fields and select a valid phone.';
        $msgType = 'danger';
    } else {
        // Verify phone is still available
        $check = $pdo->prepare("SELECT id FROM phones WHERE id = ? AND status = 'Available' LIMIT 1");
        $check->execute([$phoneId]);
        if (!$check->fetch()) {
            $message = 'This phone is no longer available.';
            $msgType = 'danger';
        } else {
            $invoiceNo   = 'INV-' . date('Y') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $warrantyEnd = date('Y-m-d', strtotime("+{$warrantyDays} days"));

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO sales (invoice_no, customer_name, customer_phone, phone_id, total_amount, payment_method, warranty_duration_days, warranty_end_date)
                     VALUES (?,?,?,?,?,?,?,?)"
                );
                $stmt->execute([$invoiceNo, $customerName, $customerPhone, $phoneId, $totalAmount, $paymentMethod, $warrantyDays, $warrantyEnd]);
                $saleId = $pdo->lastInsertId();

                $pdo->prepare("UPDATE phones SET status = 'Sold' WHERE id = ?")->execute([$phoneId]);
                $pdo->commit();

                $message = "Sale recorded! Invoice: <strong>{$invoiceNo}</strong> — <a href='/Second_Hand_Phone_Store/export.php?sale_id={$saleId}' target='_blank' class='alert-link'>Print Invoice</a>";
                $msgType = 'success';
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = 'Transaction failed. Please try again.';
                $msgType = 'danger';
            }
        }
    }
}

// Pre-fill IMEI from inventory page
$preImei = htmlspecialchars($_GET['imei'] ?? '');
// Fetch store settings for POS
$defaultWarrantyDays = (int)getStoreSetting('default_warranty_days', '30');
$currencySymbol      = getStoreSetting('currency_symbol', '₱');

// Recent sales list
$recentSales = $pdo->query(
    "SELECT s.*, p.brand, p.model, p.imei FROM sales s
     JOIN phones p ON s.phone_id = p.id
     ORDER BY s.created_at DESC LIMIT 10"
)->fetchAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="d-flex">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<main class="pv-main">
    <div class="pv-page-header">
        <h1 class="pv-page-title"><i class="fa-solid fa-cash-register"></i> Sales / POS</h1>
    </div>

    <?php if ($message): ?>
    <div class="pv-alert pv-alert-<?= $msgType ?>">
        <i class="fa-solid fa-<?= $msgType === 'success' ? 'circle-check' : 'circle-xmark' ?>"></i>
        <span><?= $message ?></span>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- POS Form -->
        <div class="col-lg-5">
            <div class="pv-pos-panel">
                <h2 class="pv-card-title mb-3"><i class="fa-solid fa-cart-shopping"></i> Record New Sale</h2>
                <form method="POST" action="/Second_Hand_Phone_Store/sales.php">
                    <input type="hidden" name="phone_id" id="posPhoneId">

                    <div class="mb-3">
                        <label class="form-label">Scan / Enter IMEI</label>
                        <div class="pv-search-wrap" style="max-width:100%">
                            <i class="fa-solid fa-barcode"></i>
                            <input type="text" class="form-control pv-input" id="posImei"
                                   placeholder="Type IMEI (15 digits)…"
                                   value="<?= $preImei ?>" autofocus required>
                        </div>
                        <div id="posPhoneInfo"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Customer Name</label>
                        <input type="text" class="form-control pv-input" name="customer_name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Customer Phone (Optional)</label>
                        <input type="text" class="form-control pv-input" name="customer_phone">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select pv-input" name="payment_method">
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="Transfer">Transfer</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Warranty (days)</label>
                            <input type="number" class="form-control pv-input" name="warranty_duration_days"
                                   id="warrantyDays" value="<?= $defaultWarrantyDays ?>" min="0" max="365">
                            <div class="form-text" id="warrantyEndPreview"></div>
                        </div>
                    </div>

                    <div class="pv-pos-summary">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Total Amount</span>
                            <div class="pv-pos-total"><?= htmlspecialchars($currencySymbol) ?><span id="posTotalDisplay">0.00</span></div>
                        </div>
                        <input type="hidden" name="total_amount" id="posPrice">
                    </div>

                    <button type="submit" class="pv-btn-primary w-100 justify-content-center mt-3 py-2">
                        <i class="fa-solid fa-check"></i> Complete Sale
                    </button>
                </form>
            </div>
        </div>

        <!-- Recent Sales Table -->
        <div class="col-lg-7">
            <div class="pv-card">
                <div class="pv-card-header">
                    <h2 class="pv-card-title"><i class="fa-solid fa-receipt"></i> Recent Sales</h2>
                </div>
                <div class="table-responsive">
                    <table class="pv-table">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Customer</th>
                                <th>Device</th>
                                <th>Amount</th>
                                <th>Warranty</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($recentSales)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No sales recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentSales as $s): ?>
                            <tr>
                                <td class="font-mono"><?= htmlspecialchars($s['invoice_no']) ?></td>
                                <td>
                                    <div><?= htmlspecialchars($s['customer_name']) ?></div>
                                    <div style="font-size:.75rem;color:var(--pv-text-muted)"><?= htmlspecialchars($s['customer_phone']) ?></div>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($s['brand'] . ' ' . $s['model']) ?></div>
                                    <div class="font-mono" style="font-size:.75rem;color:var(--pv-text-muted)"><?= htmlspecialchars($s['imei']) ?></div>
                                </td>
                                <td class="fw-700 text-primary-pv">₱<?= number_format($s['total_amount'], 2) ?></td>
                                <td>
                                    <?php
                                    $daysLeft = (int)ceil((strtotime($s['warranty_end_date']) - time()) / 86400);
                                    $wClass = $daysLeft > 0 ? 'pv-status-approved' : 'pv-status-rejected';
                                    ?>
                                    <span class="pv-status <?= $wClass ?>">
                                        <?= $daysLeft > 0 ? $daysLeft . 'd left' : 'Expired' ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/Second_Hand_Phone_Store/export.php?sale_id=<?= $s['id'] ?>" class="pv-btn-icon" title="Print Invoice" target="_blank">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php include __DIR__ . '/includes/footer.php'; ?>
