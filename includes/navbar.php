<?php
require_once __DIR__ . '/../config/auth.php';
$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<nav class="navbar pv-navbar">
    <div class="container-fluid px-3 px-lg-4">
        <div class="d-flex align-items-center gap-2">
            <button class="pv-toggler-btn d-lg-none me-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-label="Toggle navigation">
                <i class="fa-solid fa-bars-staggered"></i>
            </button>
            <a class="navbar-brand pv-brand m-0" href="/Second_Hand_Phone_Store/index.php">
                <div class="pv-brand-icon">
                    <i class="fa-solid fa-mobile-screen-button"></i>
                </div>
                <span class="pv-brand-text">Phone<span class="pv-brand-accent">Vault</span></span>
            </a>
        </div>

        <div class="d-flex align-items-center gap-2 gap-sm-3">
            <!-- Theme Toggle Button -->
            <button class="pv-theme-toggle" id="themeToggle" title="Toggle Light/Dark Theme" aria-label="Toggle theme">
                <i class="fa-solid fa-moon"></i>
            </button>

            <!-- User Dropdown -->
            <div class="dropdown">
                <a class="pv-user-pill" href="javascript:void(0);" role="button" aria-expanded="false">
                    <div class="pv-avatar-wrap">
                        <i class="fa-solid fa-circle-user"></i>
                        <span class="pv-status-dot" title="Online"></span>
                    </div>
                    <span class="pv-user-name d-none d-sm-inline"><?= htmlspecialchars($user['name']) ?></span>
                    <span class="pv-role-badge"><?= ucfirst($user['role']) ?></span>
                    <i class="fa-solid fa-chevron-down pv-dropdown-arrow ms-1" style="font-size: 0.75rem; opacity: 0.7;"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end pv-dropdown-menu">
                    <li>
                        <a class="dropdown-item <?= $currentPage === 'settings' ? 'active' : '' ?>" href="/Second_Hand_Phone_Store/settings.php">
                            <i class="fa-solid fa-gear me-2"></i>Settings
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="/Second_Hand_Phone_Store/logout.php">
                            <i class="fa-solid fa-right-from-bracket me-2"></i>Sign Out
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
