<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
requireLogin();

$pdo = getPDO();
$pageTitle = 'Returns & Refunds';
$pageScript = 'returns.js';
$pageStyle  = 'returns.css';
$message = '';
$msgType = '';
$user = currentUser();

// Handle new return claim
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'new_claim') {
    $saleId  = (int)$_POST['sale_id'];
    $phoneId = (int)$_POST['phone_id'];
    $reason  = trim($_POST['refund_reason']);
    $defect  = trim($_POST['defect_description']);
    $amount  = (float)$_POST['refund_amount'];

    if (!$saleId || !$phoneId || !$reason) {
        $message = 'Please fill in all required fields.';
        $msgType = 'danger';
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO returns_refunds (sale_id, phone_id, refund_reason, defect_description, refund_amount, status)
             VALUES (?,?,?,?,?,'Pending')"
        );
        $stmt->execute([$saleId, $phoneId, $reason, $defect, $amount]);
        $message = 'Return claim submitted successfully.';
        $msgType = 'success';
    }
}

// Handle process refund update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'process_refund') {
    $refundId = (int)$_POST['refund_id'];
    $status   = $_POST['status'];
    $amount   = (float)$_POST['refund_amount'];

    $pdo->prepare(
        "UPDATE returns_refunds SET status=?, refund_amount=?, processed_by=? WHERE id=?"
    )->execute([$status, $amount, $user['id'], $refundId]);

    if (in_array($status, ['Completed', 'Approved'])) {
        $rr = $pdo->prepare("SELECT phone_id FROM returns_refunds WHERE id=?");
        $rr->execute([$refundId]);
        $row = $rr->fetch();
        if ($row) {
            $pdo->prepare("UPDATE phones SET status='Returned' WHERE id=?")->execute([$row['phone_id']]);
        }
    }

    $message = 'Claim updated successfully.';
    $msgType = 'success';
}

// Load all returns
$returns = $pdo->query(
    "SELECT rr.*, s.invoice_no, s.customer_name, s.total_amount,
            p.brand, p.model, p.imei,
            u.full_name AS processed_by_name
     FROM returns_refunds rr
     JOIN sales s  ON rr.sale_id  = s.id
     JOIN phones p ON rr.phone_id = p.id
     LEFT JOIN users u ON rr.processed_by = u.id
     ORDER BY rr.created_at DESC"
)->fetchAll();

// Sales list for new claim dropdown
$availableSales = $pdo->query(
    "SELECT s.id, s.invoice_no, s.customer_name, s.total_amount, s.phone_id,
            p.brand, p.model
     FROM sales s JOIN phones p ON s.phone_id = p.id
     ORDER BY s.created_at DESC"
)->fetchAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="d-flex">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<main class="pv-main">
    <div class="pv-page-header">
        <h1 class="pv-page-title"><i class="fa-solid fa-rotate-left"></i> Returns &amp; Refunds</h1>
        <button class="pv-btn-primary" data-bs-toggle="collapse" data-bs-target="#newClaimForm">
            <i class="fa-solid fa-plus"></i> New Claim
        </button>
    </div>

    <?php if ($message): ?>
    <div class="pv-alert pv-alert-<?= $msgType ?>">
        <i class="fa-solid fa-<?= $msgType === 'success' ? 'circle-check' : 'circle-xmark' ?>"></i>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
    <?php endif; ?>

    <!-- New Claim Form (collapsible) -->
    <div class="collapse mb-4" id="newClaimForm">
        <div class="pv-card">
            <div class="pv-card-header">
                <h2 class="pv-card-title"><i class="fa-solid fa-file-circle-plus"></i> Submit Return Claim</h2>
            </div>
            <div class="pv-card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="new_claim">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Select Sale / Invoice <span class="text-danger">*</span></label>
                            <select class="form-select pv-input" name="sale_id" id="claimSaleSelect" required>
                                <option value="">— Select Invoice —</option>
                                <?php foreach ($availableSales as $s): ?>
                                <option value="<?= $s['id'] ?>"
                                        data-phone-id="<?= $s['phone_id'] ?>"
                                        data-amount="<?= $s['total_amount'] ?>">
                                    <?= htmlspecialchars($s['invoice_no']) ?> — <?= htmlspecialchars($s['customer_name']) ?>
                                    (<?= htmlspecialchars($s['brand'] . ' ' . $s['model']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Phone ID</label>
                            <input type="number" class="form-control pv-input" name="phone_id" id="claimPhoneId" readonly required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Refund Amount (₱)</label>
                            <input type="number" step="0.01" class="form-control pv-input" name="refund_amount" id="refundSaleAmount">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reason for Return <span class="text-danger">*</span></label>
                            <input type="text" class="form-control pv-input" name="refund_reason" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Defect / Inspection Notes</label>
                            <textarea class="form-control pv-input" name="defect_description" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="pv-btn-primary">
                                <i class="fa-solid fa-paper-plane"></i> Submit Claim
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Returns Table -->
    <div class="pv-card">
        <div class="pv-card-header">
            <h2 class="pv-card-title"><i class="fa-solid fa-list"></i> All Claims</h2>
            <span class="pv-status pv-status-available"><?= count($returns) ?> records</span>
        </div>
        <div class="table-responsive">
            <table class="pv-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th>Device</th>
                        <th>Reason</th>
                        <th>Refund</th>
                        <th>Status</th>
                        <th>Processed By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($returns)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No return claims found.</td></tr>
                <?php else: ?>
                    <?php foreach ($returns as $r):
                        $sc = 'pv-status-' . strtolower($r['status']);
                    ?>
                    <tr>
                        <td class="font-mono"><?= htmlspecialchars($r['invoice_no']) ?></td>
                        <td><?= htmlspecialchars($r['customer_name']) ?></td>
                        <td>
                            <div><?= htmlspecialchars($r['brand'] . ' ' . $r['model']) ?></div>
                            <div class="font-mono" style="font-size:.75rem;color:var(--pv-text-muted)"><?= htmlspecialchars($r['imei']) ?></div>
                        </td>
                        <td style="max-width:180px">
                            <div><?= htmlspecialchars($r['refund_reason']) ?></div>
                            <?php if ($r['defect_description']): ?>
                            <div style="font-size:.75rem;color:var(--pv-text-muted)"><?= htmlspecialchars(mb_strimwidth($r['defect_description'], 0, 60, '…')) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="fw-700 text-primary-pv">₱<?= number_format($r['refund_amount'], 2) ?></td>
                        <td><span class="pv-status <?= $sc ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                        <td style="font-size:.8rem;color:var(--pv-text-muted)"><?= htmlspecialchars($r['processed_by_name'] ?? '—') ?></td>
                        <td>
                            <?php if ($r['status'] === 'Pending'): ?>
                            <button class="pv-btn-icon"
                                    data-refund-id="<?= $r['id'] ?>"
                                    data-refund-amount="<?= $r['refund_amount'] ?>"
                                    title="Process Claim">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <?php endif; ?>
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
