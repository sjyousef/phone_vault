<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$nav = [
    ['page' => 'index',      'icon' => 'fa-gauge-high',          'label' => 'Dashboard'],
    ['page' => 'inventory',  'icon' => 'fa-boxes-stacked',       'label' => 'Inventory'],
    ['page' => 'sales',      'icon' => 'fa-cash-register',       'label' => 'Sales / POS'],
    ['page' => 'warranties', 'icon' => 'fa-shield-halved',       'label' => 'Warranties'],
    ['page' => 'returns',    'icon' => 'fa-rotate-left',         'label' => 'Returns & Refunds'],
    ['page' => 'phones-js',  'icon' => 'fa-mobile-screen-button', 'label' => 'Phones (JS CRUD)'],
];
?>
<!-- Desktop Sidebar -->
<aside class="pv-sidebar d-none d-lg-flex flex-column">
    <nav class="pv-sidebar-nav">
        <?php foreach ($nav as $item): ?>
        <a href="/Second_Hand_Phone_Store/<?= $item['page'] ?>.php"
           class="pv-sidebar-link <?= $currentPage === $item['page'] ? 'active' : '' ?>">
            <i class="fa-solid <?= $item['icon'] ?>"></i>
            <span><?= $item['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </nav>
</aside>

<!-- Mobile Offcanvas Sidebar -->
<div class="offcanvas offcanvas-start pv-offcanvas" tabindex="-1" id="sidebarOffcanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title"><i class="fa-solid fa-mobile-screen-button me-2"></i>PhoneVault</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0 pt-3">
        <nav class="pv-sidebar-nav">
            <?php foreach ($nav as $item): ?>
            <a href="/Second_Hand_Phone_Store/<?= $item['page'] ?>.php"
               class="pv-sidebar-link <?= $currentPage === $item['page'] ? 'active' : '' ?>">
                <i class="fa-solid <?= $item['icon'] ?>"></i>
                <span><?= $item['label'] ?></span>
            </a>
            <?php endforeach; ?>
        </nav>
    </div>
</div>
