<?php
/**
 * Duo Brew POS & Self-Ordering System
 * Top Navigation Bar
 */

$unreadNotifications = getUnreadNotificationCount();
$recentNotifications = getRecentNotifications(5);
$todayRange = getTodayDateRange();
$todaySales = db()->getValue(
    "SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE created_at BETWEEN ? AND ? AND payment_status = 'paid'",
    [$todayRange['start'], $todayRange['end']],
    'ss'
);
?>

<nav class="navbar navbar-expand navbar-light bg-white shadow-sm mb-0">
    <div class="container-fluid">
        <!-- Sidebar Toggle -->
        <button class="btn btn-link text-brown" id="menuToggle">
            <i class="fas fa-bars fa-lg"></i>
        </button>

        <!-- Today's Sales Badge -->
        <div class="d-none d-md-block">
            <span class="badge bg-cream text-brown px-3 py-2 rounded-pill">
                <i class="fas fa-coins me-1"></i>
                Today's Sales: <?= formatCurrency($todaySales) ?>
            </span>
        </div>

        <!-- Right Nav -->
        <ul class="navbar-nav ms-auto align-items-center">
            <!-- Notifications -->
            <li class="nav-item dropdown me-2">
                <a class="nav-link position-relative" href="#" id="notificationDropdown" 
                   data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell fa-lg text-brown"></i>
                    <?php if ($unreadNotifications > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= $unreadNotifications ?>
                        </span>
                    <?php endif; ?>
                </a>
                <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
                    <div class="dropdown-header d-flex justify-content-between align-items-center">
                        <strong>Notifications</strong>
                        <?php if ($unreadNotifications > 0): ?>
                            <button class="btn btn-sm btn-link text-decoration-none" onclick="markAllRead()">
                                Mark all read
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="notification-list">
                        <?php if (empty($recentNotifications)): ?>
                            <div class="text-center py-3 text-muted">
                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                <p class="mb-0">No notifications yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentNotifications as $notif): ?>
                                <a class="dropdown-item <?= !$notif['is_read'] ? 'unread' : '' ?>" 
                                   href="javascript:void(0)" onclick="viewNotification(<?= $notif['id'] ?>)"
                                   data-notif-id="<?= $notif['id'] ?>">
                                    <div class="d-flex">
                                        <div class="me-2">
                                            <?php
                                                $icon = 'bell';
                                                switch ($notif['type']) {
                                                    case 'low_stock': $icon = 'exclamation-triangle'; break;
                                                    case 'new_order': $icon = 'shopping-cart'; break;
                                                    case 'payment': $icon = 'credit-card'; break;
                                                }
                                            ?>
                                            <i class="fas fa-<?= $icon ?> text-<?= $notif['is_read'] ? 'muted' : 'primary' ?>"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="mb-0 small fw-medium"><?= htmlspecialchars($notif['title']) ?></p>
                                            <small class="text-muted"><?= timeAgo($notif['created_at']) ?></small>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="dropdown-footer text-center border-top">
                        <a href="<?= BASE_URL ?>admin/notifications.php" class="btn btn-sm btn-link">View All</a>
                    </div>
                </div>
            </li>

            <!-- User Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" 
                   id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar-sm bg-cream text-brown rounded-circle d-flex align-items-center justify-content-center"
                         style="width: 35px; height: 35px;">
                        <i class="fas fa-user"></i>
                    </div>
                    <span class="ms-2 d-none d-md-inline text-brown fw-medium">
                        <?= $_SESSION['full_name'] ?? 'User' ?>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                    <li>
                        <a class="dropdown-item" href="#">
                            <i class="fas fa-user-cog me-2"></i>Profile
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="<?= BASE_URL ?>auth/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>

<style>
.navbar {
    padding: 0.75rem 1rem;
    position: sticky;
    top: 0;
    z-index: 999;
}

.btn-link.text-brown {
    color: #6B3A2A !important;
    text-decoration: none;
}

.btn-link.text-brown:hover {
    color: #5A2E1F !important;
}

.notification-dropdown {
    width: 350px;
    max-height: 450px;
    overflow-y: auto;
    border: none;
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    border-radius: 12px;
}

.notification-list .dropdown-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f0f0f0;
    white-space: normal;
}

.notification-list .dropdown-item.unread {
    background-color: #FFF8F0;
}

.notification-list .dropdown-item:hover {
    background-color: #f8f9fa;
}

.notification-list .dropdown-item:last-child {
    border-bottom: none;
}

.text-brown {
    color: #6B3A2A !important;
}

.bg-cream {
    background-color: #D4A574 !important;
}
</style>

