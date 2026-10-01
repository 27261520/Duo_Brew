<?php
/**
 * Duo Brew POS & Self-Ordering System
 * Admin Dashboard
 */

$pageTitle = 'Dashboard';
$showSidebar = true;

// Require admin BEFORE header output to allow redirect
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();

// Date ranges
$today = getTodayDateRange();
$thisWeek = getWeekDateRange();
$thisMonth = getMonthDateRange();

// Stats
$todaySales = $db->getValue(
    "SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE created_at BETWEEN ? AND ? AND payment_status = 'paid'",
    [$today['start'], $today['end']], 'ss'
);

$totalOrders = $db->getValue(
    "SELECT COUNT(*) FROM orders WHERE created_at BETWEEN ? AND ?",
    [$today['start'], $today['end']], 'ss'
);

$completedToday = $db->getValue(
    "SELECT COUNT(*) FROM orders WHERE created_at BETWEEN ? AND ? AND status = 'completed'",
    [$today['start'], $today['end']], 'ss'
);

// Monthly sales for chart
$monthlySales = $db->getRows(
    "SELECT DATE(created_at) as date, COALESCE(SUM(total_amount), 0) as total
     FROM orders 
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     AND payment_status = 'paid'
     GROUP BY DATE(created_at)
     ORDER BY date ASC", [], ''
);

// Best selling products
$bestSellers = getBestSellingProducts(5, $thisMonth['start'], $thisMonth['end']);

// Recent transactions
$recentOrders = $db->getRows(
    "SELECT o.*, u.full_name as staff_name 
     FROM orders o 
     LEFT JOIN users u ON o.created_by = u.id 
     ORDER BY o.created_at DESC 
     LIMIT 10", [], ''
);

$monthSales = $db->getValue(
    "SELECT COALESCE(SUM(total_amount), 0) FROM orders 
     WHERE created_at BETWEEN ? AND ? AND payment_status = 'paid'",
    [$thisMonth['start'], $thisMonth['end']], 'ss'
);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Dashboard</h4>
        <p class="text-muted mb-0"><?= date('l, F d, Y') ?></p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-brown" onclick="location.reload()">
            <i class="fas fa-sync-alt me-1"></i> Refresh
        </button>
    </div>
</div>

<!-- Main Stat Cards (3-Column Grid) -->
<div class="row g-3 mb-4" id="dashboardStats">
    <div class="col-xl-4 col-md-4">
        <div class="stat-card h-100">
            <div class="stat-icon green">
                <i class="fas fa-coins"></i>
            </div>
            <div>
                <div class="stat-label">Today's Sales</div>
                <div class="stat-value" id="stat_todaySales"><?= formatCurrency($todaySales) ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4">
        <div class="stat-card h-100">
            <div class="stat-icon blue">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div>
                <div class="stat-label">Total Orders</div>
                <div class="stat-value" id="stat_totalOrders"><?= $totalOrders ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4">
        <div class="stat-card h-100">
            <div class="stat-icon green">
                <i class="fas fa-check-circle"></i>
            </div>
            <div>
                <div class="stat-label">Completed Today</div>
                <div class="stat-value" id="stat_completedToday"><?= $completedToday ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Sales Chart -->
    <div class="col-xl-8 col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Sales Trend (Last 30 Days)</span>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-brown" onclick="updateChart('daily')">Daily</button>
                    <button class="btn btn-outline-brown active" onclick="updateChart('monthly')">Monthly</button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="salesChart" height="250"></canvas>
            </div>
        </div>
    </div>

    <!-- Best Sellers -->
    <div class="col-xl-4 col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                <span>Best Selling Products (This Month)</span>
            </div>
            <div class="card-body">
                <?php if (empty($bestSellers)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-chart-bar fa-3x mb-2"></i>
                        <p class="mb-0">No sales data yet</p>
                    </div>
                <?php else: ?>
                    <canvas id="bestSellersChart" height="250"></canvas>
                    <div class="mt-3">
                        <?php foreach ($bestSellers as $index => $product): ?>
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                <div>
                                    <span class="badge bg-cream text-brown me-2">#<?= $index + 1 ?></span>
                                    <?= htmlspecialchars($product['product_name']) ?>
                                </div>
                                <span class="fw-bold"><?= $product['total_quantity'] ?> sold</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-2">
    <!-- Monthly Summary -->
    <div class="col-xl-4 col-md-6">
        <div class="card bg-brown text-white h-100">
            <div class="card-body text-center py-3">
                <div class="fs-1 fw-bold"><?= formatCurrency($monthSales) ?></div>
                <div class="small opacity-75">Monthly Sales</div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="row g-3 mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Recent Transactions</span>
                <a href="orders.php" class="btn btn-sm btn-brown">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Staff</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody id="recentOrders">
                            <?php if (empty($recentOrders)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No transactions yet</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                        <td>
                                            <span class="badge <?= getStatusBadge($order['status']) ?>">
                                                <?= getStatusLabel($order['status']) ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold"><?= formatCurrency($order['total_amount']) ?></td>
                                        <td>
                                            <span class="badge <?= getStatusBadge($order['payment_status']) ?>">
                                                <?= getStatusLabel($order['payment_status']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($order['staff_name'] ?? 'N/A') ?></td>
                                        <td><?= formatDateTime($order['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$extraScripts = ['https://cdn.jsdelivr.net/npm/chart.js'];
$pageScripts = '
<script>
// Sales Trend Chart
const salesCtx = document.getElementById("salesChart").getContext("2d");
const salesData = ' . json_encode($monthlySales) . ';

const labels = salesData.map(d => {
    const date = new Date(d.date);
    return date.toLocaleDateString("en-PH", { month: "short", day: "numeric" });
});
const values = salesData.map(d => parseFloat(d.total));

// Best Sellers Chart
const bestSellersCtx = document.getElementById("bestSellersChart").getContext("2d");
const bestSellers = ' . json_encode($bestSellers) . ';

if (bestSellers.length > 0) {
    new Chart(bestSellersCtx, {
        type: "doughnut",
        data: {
            labels: bestSellers.map(p => p.product_name),
            datasets: [{
                data: bestSellers.map(p => parseFloat(p.total_quantity)),
                backgroundColor: ["#6B3A2A", "#8B5E3C", "#D4A574", "#A67B5B", "#F5E6D3"],
                borderWidth: 2,
                borderColor: "#fff"
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: "bottom",
                    labels: { padding: 15, font: { size: 11 } }
                }
            },
            cutout: "65%"
        }
    });
}

new Chart(salesCtx, {
    type: "line",
    data: {
        labels: labels,
        datasets: [{
            label: "Daily Sales",
            data: values,
            borderColor: "#6B3A2A",
            backgroundColor: "rgba(107, 58, 42, 0.1)",
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: "#6B3A2A",
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return "₱" + parseFloat(context.raw).toLocaleString("en-PH", { minimumFractionDigits: 2 });
                    }
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { font: { size: 11 }, maxTicksLimit: 15 }
            },
            y: {
                beginAtZero: true,
                grid: { color: "rgba(0,0,0,0.05)" },
                ticks: {
                    font: { size: 11 },
                    callback: function(value) { return "₱" + value.toLocaleString(); }
                }
            }
        }
    }
});
</script>
';

require_once __DIR__ . '/../includes/footer.php'; 
?>