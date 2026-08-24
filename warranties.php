<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
requireLogin();

$pdo = getPDO();

// AJAX: warranty check by IMEI or invoice
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');

    if ($_GET['ajax'] === 'check') {
        $q = trim($_GET['q'] ?? '');
        $stmt = $pdo->prepare(
            "SELECT s.*, p.brand, p.model, p.imei
             FROM sales s JOIN phones p ON s.phone_id = p.id
             WHERE p.imei = ? OR s.invoice_no = ?
             ORDER BY s.created_at DESC LIMIT 1"
        );
        $stmt->execute([$q, $q]);
        $row = $stmt->fetch();
        if ($row) {
            $row['found'] = true;
            $row['created_at'] = date('M j, Y', strtotime($row['created_at']));
            echo json_encode($row);
        } else {
            echo json_encode(['found' => false]);
        }
        exit;
    }

    if ($_GET['ajax'] === 'detail') {
        $saleId = (int)($_GET['sale_id'] ?? 0);
        $stmt = $pdo->prepare(
            "SELECT s.*, p.brand, p.model, p.imei
             FROM sales s JOIN phones p ON s.phone_id = p.id
             WHERE s.id = ? LIMIT 1"
        );
        $stmt->execute([$saleId]);
        $row = $stmt->fetch();
        if ($row) {
            $row['found'] = true;
            $row['created_at'] = date('M j, Y', strtotime($row['created_at']));
            echo json_encode($row);
        } else {
            echo json_encode(['found' => false]);
        }
        exit;
    }
}

$pageTitle = 'Warranty Checker';
$pageScript = 'warranties.js';
$pageStyle  = 'warranties.css';

// All warranties
$warranties = $pdo->query(
    "SELECT s.*, p.brand, p.model, p.imei, p.condition_grade
     FROM sales s JOIN phones p ON s.phone_id = p.id
     ORDER BY s.warranty_end_date DESC"
)->fetchAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="d-flex">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<main class="pv-main">
    <div class="pv-page-header">
        <h1 class="pv-page-title"><i class="fa-solid fa-shield-halved"></i> Warranties</h1>
    </div>

    <!-- Warranty Checker -->
    <div class="pv-card mb-4">
        <div class="pv-card-header">
            <h2 class="pv-card-title"><i class="fa-solid fa-magnifying-glass"></i> Warranty Checker</h2>
        </div>
        <div class="pv-card-body">
            <div class="row g-2">
                <div class="col-12 col-md-8">
                    <div class="pv-search-wrap" style="max-width:100%">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" class="form-control pv-input" id="warrantySearchInput"
                               placeholder="Enter IMEI or Invoice Number…">
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <button class="pv-btn-primary w-100 justify-content-center" id="warrantySearchBtn">
                        <i class="fa-solid fa-shield-halved"></i> Check Warranty
                    </button>
                </div>
            </div>
            <div class="pv-warranty-result" id="warrantyResult"></div>
        </div>
    </div>

    <!-- Warranties Table -->
    <div class="pv-card">
        <div class="pv-card-header">
            <h2 class="pv-card-title"><i class="fa-solid fa-list"></i> All Warranties</h2>
            <span class="pv-status pv-status-available"><?= count($warranties) ?> records</span>
        </div>
        <div class="table-responsive">
            <table class="pv-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th>Device / IMEI</th>
                        <th>Sold On</th>
                        <th>Warranty End</th>
                        <th>Remaining</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($warranties)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No warranty records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($warranties as $w):
                        $daysLeft = (int)ceil((strtotime($w['warranty_end_date']) - time()) / 86400);
                        $pct      = max(0, min(100, round(($daysLeft / $w['warranty_duration_days']) * 100)));
                        $expired  = $daysLeft <= 0;
                    ?>
                    <tr>
                        <td class="font-mono"><?= htmlspecialchars($w['invoice_no']) ?></td>
                        <td>
                            <div><?= htmlspecialchars($w['customer_name']) ?></div>
                            <div style="font-size:.75rem;color:var(--pv-text-muted)"><?= htmlspecialchars($w['customer_phone']) ?></div>
                        </td>
                        <td>
                            <div><?= htmlspecialchars($w['brand'] . ' ' . $w['model']) ?></div>
                            <div class="font-mono" style="font-size:.75rem;color:var(--pv-text-muted)"><?= htmlspecialchars($w['imei']) ?></div>
                        </td>
                        <td><?= date('M j, Y', strtotime($w['created_at'])) ?></td>
                        <td><?= date('M j, Y', strtotime($w['warranty_end_date'])) ?></td>
                        <td style="min-width:120px">
                            <div class="pv-warranty-bar">
                                <div class="pv-warranty-fill <?= $expired ? 'expired' : '' ?>" style="width:<?= $pct ?>%"></div>
                            </div>
                            <div style="font-size:.75rem;margin-top:.2rem;color:var(--pv-text-muted)">
                                <?= $expired ? '<span style="color:var(--pv-danger)">Expired</span>' : $daysLeft . ' days left' ?>
                            </div>
                        </td>
                        <td>
                            <button class="pv-btn-icon" data-warranty-sale="<?= $w['id'] ?>" title="View Details">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                            <a href="/Second_Hand_Phone_Store/export.php?sale_id=<?= $w['id'] ?>" class="pv-btn-icon" title="Print" target="_blank">
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
</main>
</div>

<?php include __DIR__ . '/includes/modals.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
