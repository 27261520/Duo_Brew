<?php
/**
 * Duo Brew POS & Self-Ordering System
 * Sidebar Navigation
 */

$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
$isAdmin = hasRole('Administrator');
$isCashier = hasRole('Cashier') || hasRole('Cashier/Staff') || hasRole('Staff');
$dashboardUrl = $isAdmin ? BASE_URL . 'admin/dashboard.php' : BASE_URL . 'pos/index.php';

// Active state indicator para sa Menu Management dropdown
$isMenuOpen = in_array($currentPage, ['categories.php', 'products.php']);
?>

<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?= $dashboardUrl ?>" class="text-decoration-none">
            <div class="d-flex align-items-center">
                <div class="logo-sm">
                    <i class="fas fa-mug-hot"></i>
                </div>
                <div class="ms-2">
                    <h5 class="mb-0 text-white fw-bold"><?= BUSINESS_NAME ?></h5>
                    <small class="text-cream"><?= $isAdmin ? 'POS System' : 'Cashier' ?></small>
                </div>
            </div>
        </a>
        <button class="btn btn-link text-white d-md-none" id="sidebarToggle">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="sidebar-user">
        <div class="d-flex align-items-center">
            <div class="user-avatar">
                <i class="fas fa-user-circle fa-2x text-cream"></i>
            </div>
            <div class="ms-2">
                <p class="mb-0 text-white fw-medium"><?= $_SESSION['full_name'] ?? 'User' ?></p>
                <small class="text-cream">
                    <span class="badge bg-cream text-brown"><?= $_SESSION['role'] ?? 'N/A' ?></span>
                </small>
            </div>
        </div>
    </div>

    <ul class="sidebar-nav">
        <!-- Admin Only Dashboard -->
        <?php if ($isAdmin): ?>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin/dashboard.php" 
               class="nav-link <?= $currentDir == 'admin' && $currentPage == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin/users.php" 
               class="nav-link <?= $currentPage == 'users.php' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Collapsible Menu Management (Categories + Products) -->
        <?php if ($isAdmin): ?>
        <li class="nav-item">
            <a href="#menuSubmenu" 
               data-bs-toggle="collapse" 
               aria-expanded="<?= $isMenuOpen ? 'true' : 'false' ?>" 
               class="nav-link d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-box-open"></i>
                    <span>Menu Management</span>
                </div>
                <i class="fas fa-chevron-down small" id="menuChevron" style="transition: transform 0.3s; <?= $isMenuOpen ? 'transform: rotate(180deg);' : '' ?>"></i>
            </a>
            <div class="collapse <?= $isMenuOpen ? 'show' : '' ?>" id="menuSubmenu">
                <ul class="sidebar-nav ms-3 border-start border-white border-opacity-10 style-none">
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>admin/categories.php" 
                           class="nav-link <?= $currentPage == 'categories.php' ? 'active' : '' ?>">
                            <i class="fas fa-tags"></i>
                            <span>Categories</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>admin/products.php" 
                           class="nav-link <?= $currentPage == 'products.php' ? 'active' : '' ?>">
                            <i class="fas fa-coffee"></i>
                            <span>Products</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        <?php endif; ?>

        <li class="nav-item">
            <a href="<?= BASE_URL ?>pos/index.php" 
               class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/pos/') !== false ? 'active' : '' ?>">
                <i class="fas fa-cash-register"></i>
                <span>POS</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin/orders.php" 
               class="nav-link <?= $currentPage == 'orders.php' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-list"></i>
                <span>Orders</span>
                <?php 
                    $dbInstance = Database::getInstance();
                    $pendingCount = $dbInstance->getValue("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
                    if ($pendingCount > 0): 
                ?>
                    <span class="badge bg-danger ms-auto"><?= $pendingCount ?></span>
                <?php endif; ?>
            </a>
        </li>

        <?php if ($isAdmin): ?>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin/reports.php" 
               class="nav-link <?= $currentPage == 'reports.php' ? 'active' : '' ?>">
                <i class="fas fa-file-alt"></i>
                <span>Reports</span>
            </a>
        </li>
        <?php endif; ?>

        <li class="nav-divider"></li>

        <li class="nav-item">
            <a href="<?= SELF_ORDER_URL ?>" target="_blank" class="nav-link">
                <i class="fas fa-utensils"></i>
                <span>Self-Ordering</span>
                <i class="fas fa-external-link-alt ms-auto small"></i>
            </a>
        </li>

        <li class="nav-item">
            <a href="<?= BASE_URL ?>auth/logout.php" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var menuSubmenu = document.getElementById('menuSubmenu');
    var chevron = document.getElementById('menuChevron');
    if (menuSubmenu && chevron) {
        menuSubmenu.addEventListener('show.bs.collapse', function () {
            chevron.style.transform = 'rotate(180deg)';
        });
        menuSubmenu.addEventListener('hide.bs.collapse', function () {
            chevron.style.transform = 'rotate(0deg)';
        });
    }
});
</script>

<style>
.sidebar {
    min-width: 260px;
    max-width: 260px;
    height: 100vh;
    background: linear-gradient(180deg, #6B3A2A 0%, #5A2E1F 100%);
    transition: all 0.3s;
    position: fixed;
    z-index: 1000;
    overflow-y: auto;
    overflow-x: hidden;
}

.sidebar::-webkit-scrollbar {
    width: 5px;
}
.sidebar::-webkit-scrollbar-track {
    background: transparent;
}
.sidebar::-webkit-scrollbar-thumb {
    background: rgba(212, 165, 116, 0.4);
    border-radius: 4px;
}
.sidebar::-webkit-scrollbar-thumb:hover {
    background: #D4A574;
}

.sidebar.active {
    margin-left: -260px;
}

.sidebar-header {
    padding: 1.25rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.logo-sm {
    width: 45px;
    height: 45px;
    background: #D4A574;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo-sm i {
    font-size: 1.5rem;
    color: #6B3A2A;
}

.text-cream {
    color: #D4A574 !important;
}

.bg-cream {
    background-color: #D4A574 !important;
}

.text-brown {
    color: #6B3A2A !important;
}

.sidebar-user {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-nav {
    list-style: none;
    padding: 0.5rem 0;
    margin: 0;
}

.nav-item .nav-link {
    display: flex;
    align-items: center;
    padding: 0.75rem 1.25rem;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    transition: all 0.3s;
    gap: 10px;
}

.nav-item .nav-link:hover {
    color: #fff;
    background: rgba(255,255,255,0.1);
}

.nav-item .nav-link.active {
    color: #fff;
    background: rgba(255,255,255,0.15);
    border-right: 3px solid #D4A574;
}

.nav-item .nav-link i:first-child {
    width: 20px;
    text-align: center;
}

.nav-divider {
    height: 1px;
    background: rgba(255,255,255,0.1);
    margin: 0.5rem 1.25rem;
}

#page-content-wrapper {
    width: 100%;
    margin-left: 260px;
    transition: all 0.3s;
}

#wrapper.toggled #sidebar {
    margin-left: -260px;
}

#wrapper.toggled #page-content-wrapper {
    margin-left: 0;
}

@media (max-width: 768px) {
    .sidebar {
        margin-left: -260px;
    }
    .sidebar.active {
        margin-left: 0;
    }
    #page-content-wrapper {
        margin-left: 0;
    }
    #wrapper.toggled #page-content-wrapper {
        margin-left: 0;
    }
}
</style>