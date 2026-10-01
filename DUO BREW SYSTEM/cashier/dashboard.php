<?php
/**
 * Duo Brew POS & Self-Ordering System
 * Cashier Dashboard - Simplified view for cashier role
 */

$pageTitle = 'Cashier Dashboard';
$showSidebar = true;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Allow Admins, Cashiers, and Staff roles to prevent redirect loops
requireAuth();
if (!hasAnyRole(['Administrator', 'Cashier', 'Cashier/Staff', 'Staff'])) {
    $_SESSION['error'] = 'You do not have permission to access this page.';
    redirect(BASE_URL . 'index.php');
}

require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();

// Date ranges
$today = getTodayDateRange();

// Stats for today
$todaySales = $db->getValue(
    "SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE created_at BETWEEN ? AND ? AND payment_status = 'paid'",
    [$today['start'], $today['end']], 'ss'
);

$totalOrders = $db->getValue(
    "SELECT COUNT(*) FROM orders WHERE created_at BETWEEN ? AND ?",
    [$today['start'], $today['end']], 'ss'
);

$pendingOrders = $db->getValue(
    "SELECT COUNT(*) FROM orders WHERE status = 'pending'", [], ''
);

$preparingOrders = $db->getValue(
    "SELECT COUNT(*) FROM orders WHERE status = 'preparing'", [], ''
);

$readyOrders = $db->getValue(
    "SELECT COUNT(*) FROM orders WHERE status = 'ready'", [], ''
);

$totalCups = $db->getValue(
    "SELECT COALESCE(SUM(quantity), 0) FROM order_items oi 
     JOIN orders o ON oi.order_id = o.id 
     WHERE o.created_at BETWEEN ? AND ? AND o.payment_status = 'paid'",
    [$today['start'], $today['end']], 'ss'
);

// Active orders (pending & preparing) for quick view
$activeOrders = $db->getRows(
    "SELECT o.*, u.full_name as staff_name 
     FROM orders o 
     LEFT JOIN users u ON o.created_by = u.id 
     WHERE o.status IN ('pending', 'preparing', 'ready')
     ORDER BY FIELD(o.status, 'pending', 'preparing', 'ready'), o.created_at ASC
     LIMIT 20", [], ''
);

// Recent completed orders
$recentCompleted = $db->getRows(
    "SELECT o.*, u.full_name as staff_name 
     FROM orders o 
     LEFT JOIN users u ON o.created_by = u.id 
     WHERE o.status = 'completed'
     ORDER BY o.updated_at DESC 
     LIMIT 5", [], ''
);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="fas fa-cash-register me-2 text-brown"></i>Cashier Dashboard</h4>
        <p class="text-muted mb-0"><?= date('l, F d, Y') ?> &middot; Welcome, <?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>pos/index.php" class="btn btn-brown">
            <i class="fas fa-cash-register me-1"></i> Open POS
        </a>
        <button class="btn btn-outline-brown" onclick="location.reload()">
            <i class="fas fa-sync-alt me-1"></i> Refresh
        </button>
    </div>
</div>

<!-- Today's Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-coins"></i></div>
            <div>
                <div class="stat-label">Today's Sales</div>
                <div class="stat-value"><?= formatCurrency($todaySales) ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-shopping-cart"></i></div>
            <div>
                <div class="stat-label">Total Orders Today</div>
                <div class="stat-value"><?= $totalOrders ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon teal"><i class="fas fa-mug-hot"></i></div>
            <div>
                <div class="stat-label">Cups Sold Today</div>
                <div class="stat-value"><?= $totalCups ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-hourglass-half"></i></div>
            <div>
                <div class="stat-label">Active Orders</div>
                <div class="stat-value"><?= $pendingOrders + $preparingOrders ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Order Status Quick Count -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-warning">
            <div class="card-body d-flex justify-content-between align-items-center py-3">
                <div>
                    <h6 class="mb-1 text-warning"><i class="fas fa-clock me-1"></i> Pending</h6>
                    <span class="text-muted small">Awaiting preparation</span>
                </div>
                <span class="fs-2 fw-bold text-warning"><?= $pendingOrders ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-info">
            <div class="card-body d-flex justify-content-between align-items-center py-3">
                <div>
                    <h6 class="mb-1 text-info"><i class="fas fa-fire me-1"></i> Preparing</h6>
                    <span class="text-muted small">Being made</span>
                </div>
                <span class="fs-2 fw-bold text-info"><?= $preparingOrders ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-success">
            <div class="card-body d-flex justify-content-between align-items-center py-3">
                <div>
                    <h6 class="mb-1 text-success"><i class="fas fa-check-circle me-1"></i> Ready</h6>
                    <span class="text-muted small">Ready for pickup</span>
                </div>
                <span class="fs-2 fw-bold text-success"><?= $readyOrders ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Active Orders List -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list me-1"></i> Active Orders</span>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>admin/orders.php?status=pending" class="btn btn-sm btn-outline-warning">Pending</a>
            <a href="<?= BASE_URL ?>admin/orders.php?status=preparing" class="btn btn-sm btn-outline-info">Preparing</a>
            <a href="<?= BASE_URL ?>admin/orders.php?status=ready" class="btn btn-sm btn-outline-success">Ready</a>
            <a href="<?= BASE_URL ?>admin/orders.php" class="btn btn-sm btn-brown">View All</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($activeOrders)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="fas fa-check-circle fa-2x mb-2 d-block"></i>
                                No active orders. All caught up!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($activeOrders as $order): 
                            $items = $db->getRows(
                                "SELECT product_name, quantity FROM order_items WHERE order_id = ?", 
                                [$order['id']], 'i'
                            );
                            $itemCount = array_sum(array_column($items, 'quantity'));
                            $timeAgo = timeAgo($order['created_at']);
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                                <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                <td>
                                    <span class="badge <?= $order['order_type'] === 'self' ? 'bg-info' : 'bg-brown' ?>">
                                        <?= strtoupper($order['order_type']) ?>
                                    </span>
                                </td>
                                <td><?= $itemCount ?> item(s)</td>
                                <td class="fw-bold"><?= formatCurrency($order['total_amount']) ?></td>
                                <td>
                                    <span class="badge <?= getStatusBadge($order['status']) ?> d-block mb-1">
                                        <?= getStatusLabel($order['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= getStatusBadge($order['payment_status']) ?>">
                                        <?= getStatusLabel($order['payment_status']) ?>
                                    </span>
                                </td>
                                <td><small class="text-muted"><?= $timeAgo ?></small></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-brown dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="fas fa-chevron-circle-down"></i> Update
                                        </button>
                                        <div class="dropdown-menu">
                                            <form method="POST" action="<?= BASE_URL ?>admin/orders.php">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                <?php if ($order['status'] === 'pending'): ?>
                                                    <button type="submit" name="status" value="preparing" class="dropdown-item">
                                                        <i class="fas fa-fire text-info me-2"></i> Start Preparing
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($order['status'] === 'preparing'): ?>
                                                    <button type="submit" name="status" value="ready" class="dropdown-item">
                                                        <i class="fas fa-check-circle text-success me-2"></i> Mark Ready
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($order['status'] === 'ready'): ?>
                                                    <button type="submit" name="status" value="completed" class="dropdown-item">
                                                        <i class="fas fa-check-double text-secondary me-2"></i> Complete Order
                                                    </button>
                                                <?php endif; ?>
                                                <?php if (in_array($order['status'], ['pending', 'preparing'])): ?>
                                                    <div class="dropdown-divider"></div>
                                                    <button type="submit" name="status" value="cancelled" class="dropdown-item text-danger">
                                                        <i class="fas fa-times me-2"></i> Cancel Order
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm btn-outline-info mt-1" onclick="viewOrder(<?= $order['id'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Completed -->
<?php if (!empty($recentCompleted)): ?>
<div class="card">
    <div class="card-header">
        <span><i class="fas fa-history me-1"></i> Recently Completed</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Completed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentCompleted as $order): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                            <td><?= htmlspecialchars($order['customer_name']) ?></td>
                            <td class="fw-bold"><?= formatCurrency($order['total_amount']) ?></td>
                            <td><small class="text-muted"><?= timeAgo($order['updated_at']) ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- View Order Modal -->
<div class="modal fade" id="viewOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetails">
                <div class="text-center py-3">
                    <div class="spinner-border text-brown" role="status"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-brown" onclick="printOrder()">
                    <i class="fas fa-print me-1"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$pageScripts = '
<script>
function getStatusBadgeClass(status) {
    var map = {
        "pending": "bg-warning",
        "preparing": "bg-info",
        "ready": "bg-success",
        "completed": "bg-secondary",
        "cancelled": "bg-danger"
    };
    return map[status] || "bg-primary";
}

function viewOrder(orderId) {
    var modal = new bootstrap.Modal(document.getElementById("viewOrderModal"));
    modal.show();
    document.getElementById("orderDetails").innerHTML = "<div class=\"text-center py-3\"><div class=\"spinner-border text-brown\" role=\"status\"></div></div>";
    
    fetch("' . BASE_URL . 'admin/orders_api.php?action=get_order&id=" + orderId)
        .then(function(res) { return res.json(); })
        .then(function(data) {
            var html = "";
            if (data.error) {
                html = "<div class=\"alert alert-danger\">" + data.error + "</div>";
            } else {
                html = "<div class=\"receipt\">";
                html += "<div class=\"receipt-header text-center mb-3\">";
                html += "<h5 class=\"mb-0\">' . BUSINESS_NAME . '</h5>";
                html += "<small>' . BUSINESS_ADDRESS . '</small><br>";
                html += "<small>Tel: ' . BUSINESS_CONTACT . '</small>";
                html += "<hr>";
                html += "<h6 class=\"mb-0\">Order #" + data.order.order_number + "</h6>";
                html += "<small>" + (data.order.customer_name || "Walk-in Customer") + "</small><br>";
                html += "<small>" + new Date(data.order.created_at).toLocaleString() + "</small>";
                html += "</div>";
                
                html += "<table class=\"table table-sm\">";
                html += "<thead><tr><th>Item</th><th class=\"text-center\">Qty</th><th class=\"text-end\">Price</th><th class=\"text-end\">Subtotal</th></tr></thead><tbody>";
                
                data.items.forEach(function(item) {
                    html += "<tr>";
                    html += "<td>" + escapeHtml(item.product_name) + "</td>";
                    html += "<td class=\"text-center\">" + item.quantity + "</td>";
                    html += "<td class=\"text-end\">' . CURRENCY_SYMBOL . '" + parseFloat(item.unit_price).toFixed(2) + "</td>";
                    html += "<td class=\"text-end\">' . CURRENCY_SYMBOL . '" + parseFloat(item.subtotal).toFixed(2) + "</td>";
                    html += "</tr>";
                });
                
                html += "</tbody></table>";
                html += "<div class=\"d-flex justify-content-between\"><strong>Total:</strong><strong class=\"fs-5\">' . CURRENCY_SYMBOL . '" + parseFloat(data.order.total_amount).toFixed(2) + "</strong></div>";
                html += "<hr>";
                html += "<div class=\"d-flex justify-content-between\"><span>Status:</span><span class=\"badge " + getStatusBadgeClass(data.order.status) + "\">" + data.order.status.toUpperCase() + "</span></div>";
                html += "<div class=\"d-flex justify-content-between mt-1\"><span>Payment:</span><span class=\"badge " + (data.order.payment_status === "paid" ? "bg-success" : "bg-danger") + "\">" + data.order.payment_status.toUpperCase() + "</span></div>";
                if (data.order.notes) {
                    html += "<hr><div><small class=\"text-muted\">Notes: " + escapeHtml(data.order.notes) + "</small></div>";
                }
                html += "</div>";
            }
            
            document.getElementById("orderDetails").innerHTML = html;
        })
        .catch(function() {
            document.getElementById("orderDetails").innerHTML = "<div class=\"text-danger py-3\">Error loading order</div>";
        });
}

function printOrder() {
    var content = document.getElementById("orderDetails").querySelector(".receipt");
    if (content) {
        var printWindow = window.open("", "_blank");
        printWindow.document.write("<html><head><title>Print Order</title>");
        printWindow.document.write("<link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css\" rel=\"stylesheet\">");
        printWindow.document.write("<style>body{font-family:monospace;padding:20px;max-width:300px;margin:0 auto;}.receipt-header{text-align:center;}table{width:100%;}td,th{padding:4px;}</style>");
        printWindow.document.write("</head><body>" + content.innerHTML + "</body></html>");
        printWindow.document.close();
        printWindow.print();
    }
}

function escapeHtml(str) {
    if (!str) return "";
    var div = document.createElement("div");
    div.textContent = str;
    return div.innerHTML;
}
</script>
';
require_once __DIR__ . '/../includes/footer.php';
?>