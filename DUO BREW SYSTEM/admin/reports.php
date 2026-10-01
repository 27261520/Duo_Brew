<?php
/**
 * Duo Brew POS & Self-Ordering System
 * Reports & Analytics
 */

$pageTitle = 'Reports';
$showSidebar = true;
require_once __DIR__ . '/../includes/header.php';

requireAdmin();

$db = Database::getInstance();

$reportType = $_GET['type'] ?? 'sales';
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

$startDate = $dateFrom . ' 00:00:00';
$endDate = $dateTo . ' 23:59:59';

// Sales Report Data
$dailySales = $db->getRows(
    "SELECT DATE(created_at) as date, 
            COUNT(*) as order_count, 
            COALESCE(SUM(total_amount), 0) as total_sales,
            COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) as collected
     FROM orders 
     WHERE created_at BETWEEN ? AND ?
     GROUP BY DATE(created_at)
     ORDER BY date ASC",
    [$startDate, $endDate], 'ss'
);

$totalSales = array_sum(array_column($dailySales, 'total_sales'));
$totalOrders = array_sum(array_column($dailySales, 'order_count'));

// Best selling products
$bestSellers = getBestSellingProducts(10, $startDate, $endDate);

// Payment methods
$paymentMethods = $db->getRows(
    "SELECT p.payment_method, COUNT(*) as count, COALESCE(SUM(p.amount), 0) as total
     FROM payments p
     JOIN orders o ON p.order_id = o.id
     WHERE p.payment_date BETWEEN ? AND ?
     GROUP BY p.payment_method",
    [$startDate, $endDate], 'ss'
);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Reports & Analytics</h4>
    <button class="btn btn-brown" onclick="window.print()">
        <i class="fas fa-print me-1"></i> Print Report
    </button>
</div>

<!-- Filter Card -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Report Type</label>
                <select name="type" class="form-select">
                    <option value="sales" <?= $reportType === 'sales' ? 'selected' : '' ?>>Sales Report</option>
                    <option value="products" <?= $reportType === 'products' ? 'selected' : '' ?>>Product Performance</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">From</label>
                <input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">To</label>
                <input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-brown w-100">Generate Report</button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-coins"></i></div>
            <div>
                <div class="stat-label">Total Sales</div>
                <div class="stat-value"><?= formatCurrency($totalSales) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-shopping-cart"></i></div>
            <div>
                <div class="stat-label">Total Orders</div>
                <div class="stat-value"><?= $totalOrders ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon teal"><i class="fas fa-chart-line"></i></div>
            <div>
                <div class="stat-label">Total Orders</div>
                <div class="stat-value"><?= $totalOrders ?></div>
            </div>
        </div>
    </div>
</div>

<?php if ($reportType === 'sales'): ?>
<!-- Sales Report -->
<div class="row g-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">Sales Overview</div>
            <div class="card-body">
                <canvas id="salesReportChart" height="250"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Payment Methods</div>
            <div class="card-body">
                <canvas id="paymentChart" height="250"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Daily Breakdown</div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 400px;">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th class="text-center">Orders</th>
                                <th class="text-end">Sales</th>
                                <th class="text-end">Collected</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dailySales as $d): ?>
                                <tr>
                                    <td><?= formatDate($d['date']) ?></td>
                                    <td class="text-center"><?= $d['order_count'] ?></td>
                                    <td class="text-end"><?= formatCurrency($d['total_sales']) ?></td>
                                    <td class="text-end text-success"><?= formatCurrency($d['collected']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($reportType === 'products'): ?>
<!-- Product Performance Report -->
<div class="card">
    <div class="card-header">Best Selling Products</div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <canvas id="productChart" height="250"></canvas>
            </div>
            <div class="col-md-6">
                <div class="table-responsive" style="max-height: 400px;">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th class="text-center">Sold</th>
                                <th class="text-end">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bestSellers as $i => $p): ?>
                                <tr>
                                    <td class="fw-bold"><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($p['product_name']) ?></td>
                                    <td class="text-center"><?= $p['total_quantity'] ?></td>
                                    <td class="text-end fw-bold"><?= formatCurrency($p['total_revenue']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$pageScripts = '
<script>
' . ($reportType === 'sales' ? '
// Sales Chart
new Chart(document.getElementById("salesReportChart"), {
    type: "bar",
    data: {
        labels: ' . json_encode(array_map(fn($d) => date('M d', strtotime($d['date'])), $dailySales)) . ',
        datasets: [{
            label: "Sales",
            data: ' . json_encode(array_map(fn($d) => floatval($d['total_sales']), $dailySales)) . ',
            backgroundColor: "rgba(107, 58, 42, 0.7)",
            borderColor: "#6B3A2A",
            borderWidth: 1,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { callback: v => "₱" + v.toLocaleString() } }
        }
    }
});

// Payment Methods Chart
new Chart(document.getElementById("paymentChart"), {
    type: "pie",
    data: {
        labels: ' . json_encode(array_map(fn($p) => ucfirst($p['payment_method']), $paymentMethods)) . ',
        datasets: [{
            data: ' . json_encode(array_map(fn($p) => floatval($p['total']), $paymentMethods)) . ',
            backgroundColor: ["#6B3A2A", "#D4A574"],
            borderWidth: 2,
            borderColor: "#fff"
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: "bottom" },
            tooltip: {
                callbacks: {
                    label: ctx => {
                        const total = ctx.dataset.data.reduce((a,b) => a+b, 0);
                        const pct = ((ctx.raw / total) * 100).toFixed(1);
                        return ctx.label + ": ₱" + ctx.raw.toLocaleString() + " (" + pct + "%)";
                    }
                }
            }
        }
    }
});
' : '') . '

' . ($reportType === 'products' && !empty($bestSellers) ? '
// Product Performance Chart
new Chart(document.getElementById("productChart"), {
    type: "bar",
    data: {
        labels: ' . json_encode(array_map(fn($p) => $p['product_name'], $bestSellers)) . ',
        datasets: [{
            label: "Quantity Sold",
            data: ' . json_encode(array_map(fn($p) => intval($p['total_quantity']), $bestSellers)) . ',
            backgroundColor: "#6B3A2A",
            borderRadius: 6
        }]
    },
    options: {
        indexAxis: "y",
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});
' : '') . '
</script>
';
require_once __DIR__ . '/../includes/footer.php'; 
?>

