<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
requireLogin();

$pdo = getPDO();
$pageTitle = 'Inventory';
$pageScript = 'inventory.js';
$pageStyle  = 'inventory.css';
$currencySymbol = getStoreSetting('currency_symbol', '₱');
$message = '';
$msgType = '';

function getBrandIcon(string $brand): string {
    $b = strtolower(trim($brand));
    if (str_contains($b, 'apple') || str_contains($b, 'iphone')) return 'fa-brands fa-apple';
    if (str_contains($b, 'samsung')) return 'fa-solid fa-mobile-screen-button';
    if (str_contains($b, 'google') || str_contains($b, 'pixel')) return 'fa-brands fa-google';
    if (str_contains($b, 'oneplus')) return 'fa-solid fa-mobile-screen';
    if (str_contains($b, 'xiaomi') || str_contains($b, 'redmi')) return 'fa-solid fa-mobile';
    return 'fa-solid fa-folder-closed';
}

// Handle Add / Edit / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO phones (brand, model, imei, storage, color, battery_health, condition_grade, cost_price, selling_price, status)
                 VALUES (?,?,?,?,?,?,?,?,?,?)"
            );
            $stmt->execute([
                trim($_POST['brand']),
                trim($_POST['model']),
                trim($_POST['imei']),
                trim($_POST['storage']),
                trim($_POST['color']),
                (int)$_POST['battery_health'],
                $_POST['condition_grade'],
                (float)$_POST['cost_price'],
                (float)$_POST['selling_price'],
                $_POST['status'],
            ]);
            $message = 'Phone added successfully.';
            $msgType = 'success';
        } catch (PDOException $e) {
            $message = 'Error: ' . ($e->getCode() === '23000' ? 'IMEI already exists.' : 'Could not add phone.');
            $msgType = 'danger';
        }
    } elseif ($action === 'edit') {
        $stmt = $pdo->prepare(
            "UPDATE phones SET brand=?, model=?, imei=?, storage=?, color=?, battery_health=?,
             condition_grade=?, cost_price=?, selling_price=?, status=? WHERE id=?"
        );
        $stmt->execute([
            trim($_POST['brand']),
            trim($_POST['model']),
            trim($_POST['imei']),
            trim($_POST['storage']),
            trim($_POST['color']),
            (int)$_POST['battery_health'],
            $_POST['condition_grade'],
            (float)$_POST['cost_price'],
            (float)$_POST['selling_price'],
            $_POST['status'],
            (int)$_POST['phone_id'],
        ]);
        $message = 'Phone updated successfully.';
        $msgType = 'success';
    } elseif ($action === 'delete' && isAdmin()) {
        $pdo->prepare("DELETE FROM phones WHERE id = ?")->execute([(int)$_POST['phone_id']]);
        $message = 'Phone deleted.';
        $msgType = 'success';
    }
}

// Fetch all phone records ordered by brand, model
$stmt = $pdo->query("SELECT * FROM phones ORDER BY brand ASC, model ASC, created_at DESC");
$phones = $stmt->fetchAll();

// Build 3-level folder hierarchy: Brand -> Model -> Individual Phone Units
$catalog = [];
$totalAvailableStock = 0;

foreach ($phones as $p) {
    $b = $p['brand'];
    $m = $p['model'];

    if (!isset($catalog[$b])) {
        $catalog[$b] = [
            'brand' => $b,
            'total_units' => 0,
            'available_units' => 0,
            'sold_units' => 0,
            'models' => []
        ];
    }

    if (!isset($catalog[$b]['models'][$m])) {
        $catalog[$b]['models'][$m] = [
            'brand' => $b,
            'model' => $m,
            'total_units' => 0,
            'available_units' => 0,
            'sold_units' => 0,
            'returned_units' => 0,
            'min_price' => $p['selling_price'],
            'max_price' => $p['selling_price'],
            'storages' => [],
            'colors'   => [],
            'units' => []
        ];
    }

    $catalog[$b]['total_units']++;
    $catalog[$b]['models'][$m]['total_units']++;
    $catalog[$b]['models'][$m]['units'][] = $p;

    if ($p['status'] === 'Available') {
        $catalog[$b]['available_units']++;
        $catalog[$b]['models'][$m]['available_units']++;
        $totalAvailableStock++;
    } elseif ($p['status'] === 'Sold') {
        $catalog[$b]['sold_units']++;
        $catalog[$b]['models'][$m]['sold_units']++;
    } elseif ($p['status'] === 'Returned') {
        $catalog[$b]['models'][$m]['returned_units']++;
    }

    if ($p['selling_price'] < $catalog[$b]['models'][$m]['min_price']) $catalog[$b]['models'][$m]['min_price'] = $p['selling_price'];
    if ($p['selling_price'] > $catalog[$b]['models'][$m]['max_price']) $catalog[$b]['models'][$m]['max_price'] = $p['selling_price'];

    if ($p['storage'] && !in_array($p['storage'], $catalog[$b]['models'][$m]['storages'])) {
        $catalog[$b]['models'][$m]['storages'][] = $p['storage'];
    }
    if ($p['color'] && !in_array($p['color'], $catalog[$b]['models'][$m]['colors'])) {
        $catalog[$b]['models'][$m]['colors'][] = $p['color'];
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="d-flex">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<main class="pv-main">

    <!-- Page Header & Action Controls -->
    <div class="pv-page-header">
        <div>
            <h1 class="pv-page-title"><i class="fa-solid fa-folder-tree"></i> Inventory Catalog</h1>
        </div>
        <button class="pv-btn-primary" id="addPhoneBtn">
            <i class="fa-solid fa-plus"></i> Add New Phone
        </button>
    </div>

    <?php if ($message): ?>
    <div class="pv-alert pv-alert-<?= $msgType ?>">
        <i class="fa-solid fa-<?= $msgType === 'success' ? 'circle-check' : 'circle-xmark' ?>"></i>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
    <?php endif; ?>

    <!-- Interactive Navigation Breadcrumb & Search Bar -->
    <div class="pv-card mb-4">
        <div class="pv-card-body py-3">
            <div class="row g-2 align-items-center justify-content-between">
                <div class="col-12 col-md-7">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb m-0 pv-folder-breadcrumb">
                            <li class="breadcrumb-item"><a href="#" id="bcRoot"><i class="fa-solid fa-box-archive me-1"></i> Inventory Catalog</a></li>
                            <li class="breadcrumb-item active d-none" id="bcBrand">Brand</li>
                            <li class="breadcrumb-item active d-none" id="bcModel">Model</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-12 col-md-5">
                    <div class="pv-search-wrap" style="max-width:100%">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" class="form-control pv-input" id="inventorySearch" placeholder="Search brand, model (e.g. iPhone 11), IMEI…">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($phones)): ?>
    <div class="pv-empty">
        <i class="fa-solid fa-folder-open"></i>
        <p>No phones found in inventory catalog. Click "Add New Phone" to get started.</p>
    </div>
    <?php else: ?>

    <!-- ============================================================
         LEVEL 1: BRAND FOLDERS VIEW
         ============================================================ -->
    <div id="level1BrandFolders" class="pv-inventory-level">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-700 m-0"><i class="fa-solid fa-folder-closed text-primary me-2"></i> Select Phone Brand Category</h5>
            <span class="badge bg-primary-subtle text-primary fs-7"><?= count($catalog) ?> Brands Available</span>
        </div>

        <div class="row g-3">
            <?php foreach ($catalog as $bName => $bData):
                $modelCount = count($bData['models']);
                $searchData = strtolower($bName);
                $brandIcon  = getBrandIcon($bName);
                $availUnits = $bData['available_units'] ?? 0;
            ?>
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3" data-search="<?= htmlspecialchars($searchData) ?>">
                <div class="pv-folder-card" data-open-brand="<?= htmlspecialchars($bName) ?>">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="pv-folder-brand-badge">
                            <i class="<?= $brandIcon ?>"></i>
                        </div>
                        <span class="pv-folder-stock-badge">
                            <i class="fa-solid fa-circle me-1" style="font-size:.5rem;vertical-align:middle"></i> <?= $availUnits ?> Stock
                        </span>
                    </div>

                    <div class="pv-folder-body">
                        <div class="pv-folder-title"><?= htmlspecialchars($bName) ?></div>
                        <div class="pv-folder-subtext">Brand Directory Folder</div>

                        <div class="pv-folder-stats mt-3">
                            <div class="pv-stat-item">
                                <i class="fa-solid fa-layer-group text-primary me-1"></i>
                                <span><strong><?= $modelCount ?></strong> Model<?= $modelCount > 1 ? 's' : '' ?></span>
                            </div>
                            <div class="pv-stat-item ms-auto">
                                <i class="fa-solid fa-mobile-screen text-muted me-1"></i>
                                <span><strong><?= $bData['total_units'] ?? 0 ?></strong> Units</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <span class="pv-folder-open-btn">
                            Open Folder <i class="fa-solid fa-folder-open ms-1"></i>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ============================================================
         LEVEL 2: MODEL VARIANTS VIEW (e.g. iPhone 11, iPhone 12, etc.)
         ============================================================ -->
    <div id="level2ModelVariants" class="pv-inventory-level d-none">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-secondary" id="btnBackToBrands">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Brands
                </button>
                <h5 class="fw-700 m-0 ms-2"><i class="fa-solid fa-mobile-screen-button text-primary me-2"></i> <span id="currentBrandTitle">Brand</span> Models</h5>
            </div>
        </div>

        <div class="row g-3" id="modelVariantsContainer">
            <!-- Dynamically rendered or toggled via JS -->
            <?php foreach ($catalog as $bName => $bData): ?>
                <?php foreach ($bData['models'] as $mName => $mData):
                    $availCount = $mData['available_count'] ?? 0;
                    $unitsArr   = $mData['units'] ?? [];
                    $totalUnits = count($unitsArr);
                    $minPrice   = $mData['min_price'] ?? 0;
                    $maxPrice   = $mData['max_price'] ?? 0;
                    $priceText  = ($minPrice === $maxPrice) 
                        ? $currencySymbol . number_format($minPrice, 2) 
                        : $currencySymbol . number_format($minPrice, 0) . ' - ' . $currencySymbol . number_format($maxPrice, 0);
                    $storages   = $mData['storages'] ?? [];
                    $colors     = $mData['colors'] ?? [];
                    $searchData = strtolower($bName . ' ' . $mName . ' ' . implode(' ', $storages) . ' ' . implode(' ', $colors));
                    $bIcon      = getBrandIcon($bName);
                ?>
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3 d-none" 
                     data-model-folder="1" 
                     data-brand-parent="<?= htmlspecialchars($bName) ?>" 
                     data-model-name="<?= htmlspecialchars($mName) ?>"
                     data-search="<?= htmlspecialchars($searchData) ?>">
                    <div class="pv-model-card" data-open-model="<?= htmlspecialchars($mName) ?>" data-parent-brand="<?= htmlspecialchars($bName) ?>">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="pv-phone-brand"><i class="<?= $bIcon ?> me-1"></i> <?= htmlspecialchars($bName) ?></span>
                            <span class="pv-status <?= $availCount > 0 ? 'pv-status-available' : 'pv-status-rejected' ?>">
                                <?= $availCount ?> Available
                            </span>
                        </div>
                        <div class="pv-phone-model fs-5 fw-800 my-1"><?= htmlspecialchars($mName) ?></div>
                        
                        <div class="pv-phone-meta my-2">
                            <?php foreach ($storages as $st): ?>
                            <span class="pv-status pv-status-sold"><?= htmlspecialchars($st) ?></span>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted d-block" style="font-size:.7rem">Price</small>
                                <span class="fw-800 text-primary"><?= $priceText ?></span>
                            </div>
                            <span class="btn btn-sm pv-btn-primary py-1 px-3">
                                View <?= $totalUnits ?> Unit<?= $totalUnits > 1 ? 's' : '' ?> &rarr;
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ============================================================
         LEVEL 3: INDIVIDUAL DEVICE UNITS VIEW (e.g. all 5 iPhone 11 units)
         ============================================================ -->
    <div id="level3DeviceUnits" class="pv-inventory-level d-none">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-secondary" id="btnBackToModels">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Models
                </button>
                <h5 class="fw-700 m-0 ms-2">
                    <i class="fa-solid fa-mobile-screen text-primary me-2"></i>
                    <span id="unitDetailBrandTitle">Brand</span> <span id="unitDetailModelTitle">Model</span> Units
                </h5>
            </div>

            <!-- Status Filter Pills -->
            <div class="btn-group pv-status-pills" role="group" id="unitStatusFilterGroup">
                <button type="button" class="btn btn-sm btn-outline-primary active" data-unit-status="all">All Statuses</button>
                <button type="button" class="btn btn-sm btn-outline-success" data-unit-status="Available">Available</button>
                <button type="button" class="btn btn-sm btn-outline-info" data-unit-status="Sold">Sold</button>
                <button type="button" class="btn btn-sm btn-outline-warning" data-unit-status="Returned">Returned</button>
            </div>
        </div>

        <div class="pv-card">
            <div class="pv-card-body p-0">
                <div class="table-responsive">
                    <table class="pv-table align-middle m-0" id="unitDetailsTable">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>IMEI</th>
                                <th>Storage / Color</th>
                                <th>Battery Health</th>
                                <th>Grade</th>
                                <th>Cost Price</th>
                                <th>Selling Price</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($phones as $p):
                                $uBattClass = $p['battery_health'] >= 75 ? 'high' : ($p['battery_health'] >= 50 ? 'medium' : 'low');
                                $uGradeClass = match($p['condition_grade']) { 'Grade A' => 'pv-grade-a', 'Grade B' => 'pv-grade-b', default => 'pv-grade-c' };
                                $uStatusClass = 'pv-status-' . strtolower($p['status']);
                                $searchStr = strtolower($p['brand'] . ' ' . $p['model'] . ' ' . $p['imei'] . ' ' . $p['storage'] . ' ' . $p['color']);
                            ?>
                            <tr class="d-none" 
                                data-unit-row="1" 
                                data-brand="<?= htmlspecialchars($p['brand']) ?>" 
                                data-model="<?= htmlspecialchars($p['model']) ?>"
                                data-status="<?= htmlspecialchars($p['status']) ?>"
                                data-search="<?= htmlspecialchars($searchStr) ?>">
                                <td>
                                    <span class="pv-status <?= $uStatusClass ?>"><?= htmlspecialchars($p['status']) ?></span>
                                </td>
                                <td class="font-mono fw-700"><?= htmlspecialchars($p['imei']) ?></td>
                                <td>
                                    <div class="fw-600"><?= htmlspecialchars($p['storage'] ?: 'N/A') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($p['color'] ?: 'N/A') ?></small>
                                </td>
                                <td>
                                    <div class="pv-battery" style="max-width:100px">
                                        <div class="pv-battery-bar">
                                            <div class="pv-battery-fill <?= $uBattClass ?>" style="width:<?= $p['battery_health'] ?>%"></div>
                                        </div>
                                        <span style="font-size:.75rem;font-weight:600"><?= $p['battery_health'] ?>%</span>
                                    </div>
                                </td>
                                <td><span class="pv-grade <?= $uGradeClass ?>"><?= htmlspecialchars($p['condition_grade']) ?></span></td>
                                <td class="text-muted"><?= htmlspecialchars($currencySymbol) ?><?= number_format($p['cost_price'], 2) ?></td>
                                <td class="fw-800 text-primary"><?= htmlspecialchars($currencySymbol) ?><?= number_format($p['selling_price'], 2) ?></td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <!-- View Device Specs Details Modal -->
                                        <button class="pv-btn-icon"
                                            data-inspect-phone="1"
                                            data-brand="<?= htmlspecialchars($p['brand'], ENT_QUOTES) ?>"
                                            data-model="<?= htmlspecialchars($p['model'], ENT_QUOTES) ?>"
                                            data-imei="<?= htmlspecialchars($p['imei'], ENT_QUOTES) ?>"
                                            data-storage="<?= htmlspecialchars($p['storage'] ?: 'N/A', ENT_QUOTES) ?>"
                                            data-color="<?= htmlspecialchars($p['color'] ?: 'N/A', ENT_QUOTES) ?>"
                                            data-battery="<?= $p['battery_health'] ?>"
                                            data-grade="<?= htmlspecialchars($p['condition_grade'], ENT_QUOTES) ?>"
                                            data-status="<?= htmlspecialchars($p['status'], ENT_QUOTES) ?>"
                                            data-cost="<?= number_format($p['cost_price'], 2) ?>"
                                            data-price="<?= number_format($p['selling_price'], 2) ?>"
                                            data-date="<?= date('M j, Y', strtotime($p['created_at'])) ?>"
                                            title="View Full Specs & Info">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>

                                        <!-- Edit Phone Modal -->
                                        <button class="pv-btn-icon"
                                            data-edit-phone="1"
                                            data-id="<?= $p['id'] ?>"
                                            data-brand="<?= htmlspecialchars($p['brand'], ENT_QUOTES) ?>"
                                            data-model="<?= htmlspecialchars($p['model'], ENT_QUOTES) ?>"
                                            data-imei="<?= htmlspecialchars($p['imei'], ENT_QUOTES) ?>"
                                            data-storage="<?= htmlspecialchars($p['storage'], ENT_QUOTES) ?>"
                                            data-color="<?= htmlspecialchars($p['color'], ENT_QUOTES) ?>"
                                            data-battery="<?= $p['battery_health'] ?>"
                                            data-grade="<?= htmlspecialchars($p['condition_grade'], ENT_QUOTES) ?>"
                                            data-status="<?= htmlspecialchars($p['status'], ENT_QUOTES) ?>"
                                            data-cost="<?= $p['cost_price'] ?>"
                                            data-price="<?= $p['selling_price'] ?>"
                                            title="Edit Phone">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>

                                        <?php if ($p['status'] === 'Available'): ?>
                                        <a href="/Second_Hand_Phone_Store/sales.php?imei=<?= urlencode($p['imei']) ?>" class="pv-btn-icon" title="Sell in POS">
                                            <i class="fa-solid fa-cash-register"></i>
                                        </a>
                                        <?php endif; ?>

                                        <a href="/Second_Hand_Phone_Store/export.php?phone_id=<?= $p['id'] ?>" class="pv-btn-icon" title="Export PDF Sheet" target="_blank">
                                            <i class="fa-solid fa-file-pdf"></i>
                                        </a>

                                        <?php if (isAdmin()): ?>
                                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this phone unit?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="phone_id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="pv-btn-icon danger" title="Delete Unit">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</main>
</div>

<!-- Modal: View Full Device Specs Details -->
<div class="modal fade" id="inspectPhoneModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pv-modal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-mobile-screen-button me-2 text-primary"></i>Phone Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <h4 class="fw-800 m-0" id="inspBrandModel">iPhone 11</h4>
                    <span class="font-mono text-muted small" id="inspImei">IMEI: ---</span>
                </div>
                <dl class="row g-2 mb-0 border rounded p-3 bg-surface-2">
                    <dt class="col-5">Status</dt>
                    <dd class="col-7" id="inspStatus"><span class="pv-status pv-status-available">Available</span></dd>
                    
                    <dt class="col-5">Condition Grade</dt>
                    <dd class="col-7" id="inspGrade"><span class="pv-grade pv-grade-a">Grade A</span></dd>

                    <dt class="col-5">Storage</dt>
                    <dd class="col-7" id="inspStorage">128GB</dd>

                    <dt class="col-5">Color</dt>
                    <dd class="col-7" id="inspColor">Black</dd>

                    <dt class="col-5">Battery Health</dt>
                    <dd class="col-7" id="inspBattery">89%</dd>

                    <dt class="col-5">Cost Price</dt>
                    <dd class="col-7 font-mono" id="inspCost">₱0.00</dd>

                    <dt class="col-5">Selling Price</dt>
                    <dd class="col-7 font-mono fw-800 text-primary" id="inspPrice">₱0.00</dd>

                    <dt class="col-5">Added Date</dt>
                    <dd class="col-7" id="inspDate">Jan 1, 2026</dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn pv-btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/modals.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
