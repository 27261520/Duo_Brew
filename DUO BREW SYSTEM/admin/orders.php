<?php
/**
 * Duo Brew POS & Self-Ordering System
 * Orders Management (Streamlined for Single-Staff Setup)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = Database::getInstance();

// Handle status update (Only used for Cancelling orders)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $orderId = intval($_POST['order_id']);
        $status = sanitize($_POST['status']);
        
        $db->execute(
            "UPDATE orders SET status = ? WHERE id = ?",
            [$status, $orderId], 'si'
        );
        logActivity('Update Order Status', "Order #$orderId status: $status");
        redirectWith('orders.php', 'Order status updated!');
    }
}

$pageTitle = 'Orders';
$showSidebar = true;
require_once __DIR__ . '/../includes/header.php';

$sql = "SELECT o.*, u.full_name as staff_name 
        FROM orders o 
        LEFT JOIN users u ON o.created_by = u.id
        ORDER BY o.created_at DESC";
$orders = $db->getRows($sql);

// Fetch recent completed or paid transactions for quick access
$recentTransactions = $db->getRows(
    "SELECT o.*, 
            COALESCE((SELECT SUM(quantity) FROM order_items WHERE order_id = o.id), 0) as total_items
     FROM orders o 
     WHERE o.status IN ('completed', 'paid') 
     ORDER BY o.created_at DESC 
     LIMIT 15",
    [],
    ''
);
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="mb-0">Orders</h4>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <button type="button" class="btn btn-sm btn-outline-brown" data-bs-toggle="modal" data-bs-target="#recentTransactionsModal">
            <i class="fas fa-history me-1"></i> Recent Transactions
        </button>
        <div class="d-flex gap-2" id="orderFilters">
            <button type="button" class="btn btn-sm btn-brown filter-btn active" data-filter="all">All</button>
            <button type="button" class="btn btn-sm btn-outline-brown filter-btn" data-filter="completed">Completed</button>
            <button type="button" class="btn btn-sm btn-outline-brown filter-btn" data-filter="cancelled">Cancelled</button>
        </div>
    </div>
</div>

<div class="card">
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
                        <th>Staff</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr class="no-orders-row"><td colspan="10" class="text-center py-4 text-muted">No orders found</td></tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): 
                            $items = $db->getRows(
                                "SELECT product_name, quantity FROM order_items WHERE order_id = ?", 
                                [$order['id']], 'i'
                            );
                            $itemCount = array_sum(array_column($items, 'quantity'));
                        ?>
                            <tr data-status="<?= $order['status'] ?>">
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
                                    <span class="badge <?= $order['status'] === 'completed' ? 'bg-success' : ($order['status'] === 'cancelled' ? 'bg-danger' : 'bg-secondary') ?> d-block mb-1" style="width:fit-content;">
                                        <?= strtoupper(htmlspecialchars($order['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= getStatusBadge($order['payment_status']) ?>">
                                        <?= getStatusLabel($order['payment_status']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($order['staff_name'] ?? 'N/A') ?></td>
                                <td><?= formatDateTime($order['created_at']) ?></td>
                                <td class="d-flex gap-1">
                                    <button class="btn btn-sm btn-outline-info" onclick="viewOrder(<?= $order['id'] ?>)" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($order['status'] !== 'cancelled'): ?>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this order? This action cannot be undone.');">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <input type="hidden" name="status" value="cancelled">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancel Order">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Transactions Modal -->
<div class="modal fade" id="recentTransactionsModal" tabindex="-1" aria-labelledby="recentTransactionsLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-brown text-white" style="background: #6B3A2A;">
                <h5 class="modal-title text-white mb-0" id="recentTransactionsLabel">
                    <i class="fas fa-history me-2"></i>Recent Completed Transactions
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Type</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentTransactions)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">
                                        No recent completed transactions found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentTransactions as $tx): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-brown" style="color: #6B3A2A;">
                                                #<?= htmlspecialchars($tx['order_number'] ?? $tx['id']) ?>
                                            </strong>
                                        </td>
                                        <td><?= htmlspecialchars($tx['customer_name'] ?? 'Walk-in') ?></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?= ucfirst(htmlspecialchars($tx['order_type'] ?? 'Dine-in')) ?>
                                            </span>
                                        </td>
                                        <td><?= intval($tx['total_items']) ?> item(s)</td>
                                        <td class="fw-bold"><?= formatCurrency($tx['total_amount'] ?? 0) ?></td>
                                        <td>
                                            <span class="badge bg-info text-dark">
                                                <?= strtoupper(htmlspecialchars($tx['payment_status'] ?? 'paid')) ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted">
                                            <?= date('h:i A', strtotime($tx['created_at'])) ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i>Completed
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info" onclick="viewOrder(<?= $tx['id'] ?>)" data-bs-dismiss="modal">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <small class="text-muted me-auto">Showing the latest 15 completed transactions</small>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

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
// Store constants for JS
$jsBusinessName = BUSINESS_NAME;
$jsBusinessAddress = BUSINESS_ADDRESS;
$jsBusinessContact = BUSINESS_CONTACT;
$jsCurrencySymbol = CURRENCY_SYMBOL;
$pageScripts = <<<'EOSCRIPT'
<script>
(function() {
    'use strict';

    /**
     * Update order count badges on filter buttons.
     */
    function updateFilterCounts() {
        var rows = document.querySelectorAll("table tbody tr[data-status]");
        var counts = { all: rows.length };
        
        rows.forEach(function(row) {
            var status = row.getAttribute("data-status");
            counts[status] = (counts[status] || 0) + 1;
        });
        
        document.querySelectorAll("#orderFilters .filter-btn").forEach(function(btn) {
            var filter = btn.getAttribute("data-filter");
            var count = counts[filter] || 0;
            
            var badge = btn.querySelector(".filter-count");
            if (!badge) {
                badge = document.createElement("span");
                btn.appendChild(badge);
            }
            
            badge.textContent = count;
            
            // Force classes and styles here to ensure they always apply properly
            // ms-2 provides the correct spacing between the text and the pill
            badge.className = "filter-count ms-2 badge rounded-pill";
            badge.style.backgroundColor = "#E8E4E1";
            badge.style.color = "#6B3A2A";
            badge.style.padding = "0.35em 0.65em";
            badge.style.fontSize = "0.75rem";
            badge.style.fontWeight = "bold";
            badge.style.verticalAlign = "middle";
        });
    }

    /**
     * Filter orders by status on the client side
     */
    function filterOrders(status, animate) {
        if (animate === undefined) animate = true;
        
        document.querySelectorAll("#orderFilters .filter-btn").forEach(function(btn) {
            btn.classList.remove("btn-brown", "active");
            btn.classList.add("btn-outline-brown");
        });
        
        const activeBtn = document.querySelector('#orderFilters .filter-btn[data-filter="' + status + '"]');
        if (activeBtn) {
            activeBtn.classList.remove("btn-outline-brown");
            activeBtn.classList.add("btn-brown", "active");
        }
        
        var rows = document.querySelectorAll("table tbody tr[data-status]");
        var visibleCount = 0;
        rows.forEach(function(row) {
            if (status === "all" || row.getAttribute("data-status") === status) {
                if (animate) {
                    row.style.opacity = "0";
                    row.style.transform = "scale(0.98)";
                    setTimeout(function() {
                        row.style.display = "";
                        setTimeout(function() {
                            row.style.opacity = "1";
                            row.style.transform = "scale(1)";
                        }, 20);
                    }, 10);
                } else {
                    row.style.display = "";
                }
                visibleCount++;
            } else {
                if (animate) {
                    row.style.opacity = "0";
                    row.style.transform = "scale(0.98)";
                    setTimeout(function() {
                        row.style.display = "none";
                    }, 200);
                } else {
                    row.style.display = "none";
                }
            }
        });
        
        var noOrdersRow = document.querySelector("table tbody tr.no-orders-row");
        if (noOrdersRow) {
            if (visibleCount === 0) {
                var td = noOrdersRow.querySelector("td");
                if (td) {
                    td.textContent = status === "all" ? "No orders found" : "No " + status + " orders found";
                }
                noOrdersRow.style.display = "";
            } else {
                noOrdersRow.style.display = "none";
            }
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        var filterBtns = document.querySelectorAll("#orderFilters .filter-btn");
        filterBtns.forEach(function(btn) {
            btn.addEventListener("click", function(e) {
                var status = this.getAttribute("data-filter");
                filterOrders(status);
            });
        });
        
        updateFilterCounts();
    });

    window.getStatusBadgeClass = function(status) {
        if (status === "completed") return "bg-success";
        if (status === "cancelled") return "bg-danger";
        return "bg-secondary";
    };

    window.viewOrder = function(orderId) {
        var modal = new bootstrap.Modal(document.getElementById("viewOrderModal"));
        modal.show();
        
        fetch("orders_api.php?action=get_order&id=" + orderId)
            .then(function(res) { return res.json(); })
            .then(function(data) {
                var html = '';
                html += '<div class="receipt" style="max-width: 100%;">';
                html += '<div class="receipt-header">';
                html += '<h5 class="mb-0">' + '<?= $jsBusinessName ?>' + '</h5>';
                html += '<small>' + '<?= $jsBusinessAddress ?>' + '</small><br>';
                html += '<small>Tel: ' + '<?= $jsBusinessContact ?>' + '</small>';
                html += '<hr>';
                html += '<h6 class="mb-0">Order #' + data.order.order_number + '</h6>';
                html += '<small>' + data.order.customer_name + '</small><br>';
                html += '<small>' + new Date(data.order.created_at).toLocaleString() + '</small>';
                html += '</div>';
                html += '<div class="receipt-items">';
                html += '<table class="table table-sm">';
                html += '<thead><tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Subtotal</th></tr></thead>';
                html += '<tbody>';
                
                data.items.forEach(function(item) {
                    html += '<tr>';
                    html += '<td>' + item.product_name + '</td>';
                    html += '<td class="text-center">' + item.quantity + '</td>';
                    html += '<td class="text-end">' + '<?= $jsCurrencySymbol ?>' + parseFloat(item.unit_price).toFixed(2) + '</td>';
                    html += '<td class="text-end">' + '<?= $jsCurrencySymbol ?>' + parseFloat(item.subtotal).toFixed(2) + '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
                html += '<div class="d-flex justify-content-between">';
                html += '<strong>Total:</strong>';
                html += '<strong class="fs-5">' + '<?= $jsCurrencySymbol ?>' + parseFloat(data.order.total_amount).toFixed(2) + '</strong>';
                html += '</div><hr>';
                html += '<div class="d-flex justify-content-between">';
                html += '<span>Status:</span>';
                html += '<span class="badge ' + window.getStatusBadgeClass(data.order.status) + '">' + data.order.status.toUpperCase() + '</span>';
                html += '</div>';
                html += '<div class="d-flex justify-content-between mt-1">';
                html += '<span>Payment:</span>';
                html += '<span class="badge ' + (data.order.payment_status === "paid" ? "bg-success" : "bg-danger") + '">' + data.order.payment_status.toUpperCase() + '</span>';
                html += '</div></div>';
                
                document.getElementById("orderDetails").innerHTML = html;
            })
            .catch(function() {
                document.getElementById("orderDetails").innerHTML = '<div class="text-danger py-3">Error loading order</div>';
            });
    };

    window.printOrder = function() {
        var content = document.getElementById("orderDetails").querySelector(".receipt");
        if (content) {
            printReceipt(content.innerHTML);
        }
    };

})();
</script>
EOSCRIPT;
require_once __DIR__ . '/../includes/footer.php'; 
?>