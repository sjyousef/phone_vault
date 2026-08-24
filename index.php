<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
requireLogin();

$pdo = getPDO();
$pageTitle = 'Dashboard';
$pageScript = 'dashboard.js';
$pageStyle  = 'dashboard.css';

$currencySymbol   = getStoreSetting('currency_symbol', '₱');
$batteryThreshold = (int)getStoreSetting('low_battery_threshold', '75');

// Stats (All 100% dynamic from MySQL DB)
$totalPhones      = $pdo->query("SELECT COUNT(*) FROM phones WHERE status = 'Available'")->fetchColumn();
$activeWarranties = $pdo->query("SELECT COUNT(*) FROM sales WHERE warranty_end_date >= CURDATE()")->fetchColumn();
$pendingRefunds   = $pdo->query("SELECT COUNT(*) FROM returns_refunds WHERE status = 'Pending'")->fetchColumn();

// Sales stats (Today vs Total)
$todaySales       = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$todaySalesCount  = $pdo->query("SELECT COUNT(*) FROM sales WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$totalRevenue     = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM sales")->fetchColumn();
$totalSalesCount  = $pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn();

// Brand Distribution Analytics (DB)
$brandStats = $pdo->query("SELECT brand, COUNT(*) AS count FROM phones WHERE status = 'Available' GROUP BY brand ORDER BY count DESC")->fetchAll();

// Sales Trend Analytics (DB)
$salesTrend = $pdo->query(
    "SELECT DATE_FORMAT(created_at, '%b %d') AS sale_date, SUM(total_amount) AS total, COUNT(*) AS count 
     FROM sales 
     GROUP BY DATE(created_at) 
     ORDER BY created_at ASC LIMIT 7"
)->fetchAll();

// Recent sales
$recentSales = $pdo->query(
    "SELECT s.invoice_no, s.customer_name, s.total_amount, s.payment_method, s.created_at,
            p.brand, p.model
     FROM sales s JOIN phones p ON s.phone_id = p.id
     ORDER BY s.created_at DESC LIMIT 5"
)->fetchAll();

// Low battery phones
$lowBattery = $pdo->query(
    "SELECT brand, model, imei, battery_health, condition_grade
     FROM phones WHERE battery_health < {$batteryThreshold} AND status = 'Available'
     ORDER BY battery_health ASC LIMIT 5"
)->fetchAll();

// Prepare JSON payload for Chart.js
$brandLabels = array_column($brandStats, 'brand');
$brandCounts = array_column($brandStats, 'count');

$trendLabels = array_column($salesTrend, 'sale_date');
$trendTotals = array_map('floatval', array_column($salesTrend, 'total'));

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="d-flex">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<main class="pv-main">
    <div class="pv-page-header">
        <div>
            <h1 class="pv-page-title"><i class="fa-solid fa-gauge-high"></i> Dashboard Analytics</h1>
            <div class="pv-page-subtitle"><i class="fa-regular fa-calendar me-1"></i><span class="pv-date-str"><?= date('l, F j, Y') ?></span></div>
        </div>
        <div class="d-flex gap-2">
            <a href="/Second_Hand_Phone_Store/sales.php" class="pv-btn-primary">
                <i class="fa-solid fa-cash-register"></i> POS / New Sale
            </a>
            <a href="/Second_Hand_Phone_Store/inventory.php" class="pv-btn-secondary">
                <i class="fa-solid fa-plus"></i> Add Phone
            </a>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="pv-stat-card">
                <div class="pv-stat-icon primary"><i class="fa-solid fa-mobile-screen"></i></div>
                <div>
                    <div class="pv-stat-value"><?= $totalPhones ?></div>
                    <div class="pv-stat-label">Phones in Stock</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="pv-stat-card">
                <div class="pv-stat-icon success"><i class="fa-solid fa-shield-halved"></i></div>
                <div>
                    <div class="pv-stat-value"><?= $activeWarranties ?></div>
                    <div class="pv-stat-label">Active Warranties</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="pv-stat-card">
                <div class="pv-stat-icon warning"><i class="fa-solid fa-rotate-left"></i></div>
                <div>
                    <div class="pv-stat-value"><?= $pendingRefunds ?></div>
                    <div class="pv-stat-label">Pending Refunds</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="pv-stat-card">
                <div class="pv-stat-icon info"><i class="fa-solid fa-chart-line"></i></div>
                <div>
                    <div class="pv-stat-value"><?= htmlspecialchars($currencySymbol) ?><?= number_format($todaySales, 2) ?></div>
                    <div class="pv-stat-label">Today's Sales (<?= $todaySalesCount ?>)</div>
                    <small class="text-muted" style="font-size: 0.72rem;">Total: <?= htmlspecialchars($currencySymbol) ?><?= number_format($totalRevenue, 0) ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Interactive Analytics Charts Row -->
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="pv-card h-100">
                <div class="pv-card-header">
                    <h2 class="pv-card-title"><i class="fa-solid fa-chart-area"></i> Revenue & Sales Trend</h2>
                    <span class="badge bg-primary-subtle text-primary fw-600">Live Database Analytics</span>
                </div>
                <div class="pv-card-body">
                    <div style="position: relative; height: 260px; width: 100%;">
                        <canvas id="salesTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="pv-card h-100">
                <div class="pv-card-header">
                    <h2 class="pv-card-title"><i class="fa-solid fa-pie-chart"></i> Inventory by Brand</h2>
                </div>
                <div class="pv-card-body d-flex align-items-center justify-content-center">
                    <div style="position: relative; height: 240px; width: 100%;">
                        <canvas id="brandChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tables & Warnings Row -->
    <div class="row g-3">
        <!-- Recent Sales -->
        <div class="col-lg-7">
            <div class="pv-card">
                <div class="pv-card-header">
                    <h2 class="pv-card-title"><i class="fa-solid fa-receipt"></i> Recent Sales</h2>
                    <a href="/Second_Hand_Phone_Store/sales.php" class="pv-btn-secondary btn-sm">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="pv-table">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Customer</th>
                                <th>Device</th>
                                <th>Amount</th>
                                <th>Method</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($recentSales)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No sales recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentSales as $s): ?>
                            <tr>
                                <td class="font-mono fw-600"><?= htmlspecialchars($s['invoice_no']) ?></td>
                                <td><?= htmlspecialchars($s['customer_name']) ?></td>
                                <td><?= htmlspecialchars($s['brand'] . ' ' . $s['model']) ?></td>
                                <td class="fw-700 text-primary-pv"><?= htmlspecialchars($currencySymbol) ?><?= number_format($s['total_amount'], 2) ?></td>
                                <td><span class="pv-status pv-status-available"><?= htmlspecialchars($s['payment_method']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Low Battery Alert -->
        <div class="col-lg-5">
            <div class="pv-card">
                <div class="pv-card-header">
                    <h2 class="pv-card-title"><i class="fa-solid fa-battery-quarter"></i> Low Battery Phones (< <?= $batteryThreshold ?>%)</h2>
                </div>
                <div class="pv-card-body">
                    <?php if (empty($lowBattery)): ?>
                        <div class="pv-empty"><i class="fa-solid fa-battery-full"></i><p>All phones have good battery health.</p></div>
                    <?php else: ?>
                        <?php foreach ($lowBattery as $p): ?>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <div class="fw-700" style="font-size:.875rem"><?= htmlspecialchars($p['brand'] . ' ' . $p['model']) ?></div>
                                <div class="font-mono" style="font-size:.75rem;color:var(--pv-text-muted)"><?= htmlspecialchars($p['imei']) ?></div>
                            </div>
                            <div class="text-end" style="min-width:100px">
                                <div class="pv-battery">
                                    <div class="pv-battery-bar">
                                        <div class="pv-battery-fill <?= $p['battery_health'] >= 75 ? 'high' : ($p['battery_health'] >= 50 ? 'medium' : 'low') ?>"
                                             style="width:<?= $p['battery_health'] ?>%"></div>
                                    </div>
                                    <span style="font-size:.75rem;color:var(--pv-text-muted);white-space:nowrap"><?= $p['battery_health'] ?>%</span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>
</div>

<!-- Chart.js CDN for Analytics -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Embedded DB JSON Payload for Chart.js Initialization -->
<script>
window.PV_ANALYTICS = {
    brandLabels: <?= json_encode($brandLabels) ?>,
    brandCounts: <?= json_encode($brandCounts) ?>,
    trendLabels: <?= json_encode($trendLabels) ?>,
    trendTotals: <?= json_encode($trendTotals) ?>,
    currencySymbol: <?= json_encode($currencySymbol) ?>
};
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
