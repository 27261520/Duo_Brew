<?php
/**
 * Duo Brew POS & Self-Ordering System
 * Notifications Page
 */

$pageTitle = 'Notifications';
$showSidebar = true;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireStaff();

$db = Database::getInstance();

// AJAX: Get unread count
if (isset($_GET['action']) && $_GET['action'] === 'get_unread_count') {
    header('Content-Type: application/json');
    echo json_encode(['count' => (int)getUnreadNotificationCount()]);
    exit;
}

// AJAX: Get notification data with order details
if (isset($_GET['action']) && $_GET['action'] === 'get_notif_data' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $notif = getNotificationWithOrder(intval($_GET['id']));
    if (!$notif) {
        echo json_encode(['error' => 'Notification not found']);
        exit;
    }
    echo json_encode([
        'id' => $notif['id'],
        'title' => $notif['title'],
        'message' => $notif['message'],
        'type' => $notif['type'],
        'time_ago' => timeAgo($notif['created_at']),
        'order' => $notif['order'] ?? null
    ]);
    exit;
}

// AJAX: Handle POST actions (mark_read, mark_all_read)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'mark_read' && isset($_POST['id'])) {
        markNotificationRead(intval($_POST['id']));
        echo json_encode(['success' => true, 'message' => 'Marked as read']);
        exit;
    }
    
    if ($_POST['action'] === 'mark_all_read') {
        $db->execute("UPDATE notifications SET is_read = TRUE WHERE is_read = FALSE", [], '');
        echo json_encode(['success' => true, 'message' => 'All marked as read']);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Handle view notification detail (mark as read and show modal)
$viewNotif = null;
if (isset($_GET['view'])) {
    $viewId = intval($_GET['view']);
    $viewNotif = getNotificationWithOrder($viewId);
    if ($viewNotif) {
        // Mark as read if not already
        if (!$viewNotif['is_read']) {
            markNotificationRead($viewId);
            $viewNotif['is_read'] = true;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$totalNotifications = $db->getValue("SELECT COUNT(*) FROM notifications", [], '');
$totalPages = ceil($totalNotifications / $perPage);

$notifications = $db->getRows(
    "SELECT * FROM notifications ORDER BY created_at DESC LIMIT ? OFFSET ?",
    [$perPage, $offset], 'ii'
);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Notifications</h4>
    <?php if ($totalNotifications > 0): ?>
        <button onclick="markAllRead()" class="btn btn-outline-brown" id="markAllBtn">
            <i class="fas fa-check-double me-1"></i> Mark All Read
        </button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($notifications)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-bell-slash fa-3x mb-3"></i>
                <p class="mb-0">No notifications yet</p>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush" id="notificationsList">
                <?php foreach ($notifications as $notif): 
                    $icon = 'bell';
                    switch ($notif['type']) {
                        case 'low_stock': $icon = 'exclamation-triangle'; break;
                        case 'new_order': $icon = 'shopping-cart'; break;
                        case 'payment': $icon = 'credit-card'; break;
                    }
                    $color = $notif['is_read'] ? 'text-muted' : 'text-brown';
                ?>
                    <div class="list-group-item list-group-item-action <?= !$notif['is_read'] ? 'list-group-item-warning' : '' ?> notif-item" 
                         data-notif-id="<?= $notif['id'] ?>"
                         onclick="viewNotifDetail(<?= $notif['id'] ?>)"
                         style="cursor:pointer;">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-<?= $icon ?> fa-lg <?= $color ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <strong class="<?= !$notif['is_read'] ? 'fw-bold' : '' ?>"><?= htmlspecialchars($notif['title']) ?></strong>
                                    <small class="text-muted"><?= timeAgo($notif['created_at']) ?></small>
                                </div>
                                <?php if ($notif['message']): ?>
                                    <p class="mb-0 small text-muted"><?= htmlspecialchars($notif['message']) ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if (!$notif['is_read']): ?>
                                <button class="btn btn-sm btn-outline-brown ms-2 mark-read-btn" 
                                        onclick="event.stopPropagation(); markSingleRead(<?= $notif['id'] ?>, this)"
                                        title="Mark as read">
                                    <i class="fas fa-check"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($totalPages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<!-- Notification Detail Modal -->
<div class="modal fade" id="notifDetailModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notifDetailTitle">Notification Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="notifDetailBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-brown" role="status"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="notifModalClosed()">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </button>
                <a href="<?= BASE_URL ?>admin/orders.php" class="btn btn-brown" id="viewOrderBtn" style="display:none;">
                    <i class="fas fa-external-link-alt me-1"></i> View Order
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$pageScripts = '
<script>
function getStatusBadgeClass(status) {
    var map = {
        "pending": "bg-warning text-dark",
        "preparing": "bg-info text-white",
        "ready": "bg-success text-white",
        "completed": "bg-secondary text-white",
        "cancelled": "bg-danger text-white"
    };
    return map[status] || "bg-primary text-white";
}

function getStatusLabel(status) {
    var map = {
        "pending": "Pending",
        "preparing": "Preparing",
        "ready": "Ready for Pickup",
        "completed": "Completed",
        "cancelled": "Cancelled",
        "paid": "Paid",
        "unpaid": "Unpaid"
    };
    return map[status] || status;
}

async function viewNotifDetail(notifId) {
    var modal = new bootstrap.Modal(document.getElementById("notifDetailModal"));
    modal.show();
    
    document.getElementById("notifDetailBody").innerHTML = 
        "<div class=\"text-center py-4\"><div class=\"spinner-border text-brown\" role=\"status\"></div></div>";
    document.getElementById("viewOrderBtn").style.display = "none";
    
    try {
        var res = await fetch("notifications.php?action=get_notif_data&id=" + notifId);
        var data = await res.json();
        
        if (data.error) {
            document.getElementById("notifDetailBody").innerHTML = 
                "<div class=\"alert alert-danger\">" + data.error + "</div>";
            return;
        }
        
        var html = "<div class=\"notif-detail\">";
        html += "<div class=\"d-flex align-items-center mb-3\">";
        html += "<i class=\"fas fa-bell fa-2x text-brown me-3\"></i>";
        html += "<div><h6 class=\"mb-1 fw-bold\">" + escapeHtml(data.title) + "</h6>";
        html += "<small class=\"text-muted\">" + data.time_ago + "</small></div></div>";
        
        if (data.message) {
            html += "<p class=\"mb-3\">" + escapeHtml(data.message) + "</p>";
        }
        
        // Show order details if this is an order-related notification
        if (data.order) {
            var o = data.order;
            html += "<hr><h6 class=\"fw-bold mb-3\"><i class=\"fas fa-shopping-cart me-2\"></i>Order Details</h6>";
            html += "<div class=\"row mb-2\"><div class=\"col-4 text-muted\">Order #:</div><div class=\"col-8 fw-bold\">" + escapeHtml(o.order_number) + "</div></div>";
            html += "<div class=\"row mb-2\"><div class=\"col-4 text-muted\">Customer:</div><div class=\"col-8\">" + escapeHtml(o.customer_name || "Walk-in Customer") + "</div></div>";
            html += "<div class=\"row mb-2\"><div class=\"col-4 text-muted\">Status:</div><div class=\"col-8\"><span class=\"badge " + getStatusBadgeClass(o.status) + "\">" + getStatusLabel(o.status) + "</span></div></div>";
            html += "<div class=\"row mb-2\"><div class=\"col-4 text-muted\">Payment:</div><div class=\"col-8\"><span class=\"badge " + (o.payment_status === "paid" ? "bg-success" : "bg-danger") + "\">" + (o.payment_status ? o.payment_status.toUpperCase() : "N/A") + "</span></div></div>";
            html += "<div class=\"row mb-2\"><div class=\"col-4 text-muted\">Total:</div><div class=\"col-8 fw-bold text-brown\">";
            html += "' . CURRENCY_SYMBOL . '" + parseFloat(o.total_amount).toFixed(2) + "</div></div>";
            
            // Items table
            if (o.items && o.items.length > 0) {
                html += "<hr><h6 class=\"fw-bold mb-2\">Items</h6>";
                html += "<table class=\"table table-sm table-bordered\"><thead><tr><th>Item</th><th class=\"text-center\">Qty</th><th class=\"text-end\">Subtotal</th></tr></thead><tbody>";
                o.items.forEach(function(item) {
                    html += "<tr><td>" + escapeHtml(item.product_name) + "</td>";
                    html += "<td class=\"text-center\">" + item.quantity + "</td>";
                    html += "<td class=\"text-end\">" + "' . CURRENCY_SYMBOL . '" + parseFloat(item.subtotal).toFixed(2) + "</td></tr>";
                });
                html += "</tbody></table>";
            }
            
            // Show View Order button
            document.getElementById("viewOrderBtn").style.display = "inline-block";
            document.getElementById("viewOrderBtn").href = "' . BASE_URL . 'admin/orders.php?order_id=" + o.id;
        }
        
        html += "</div>";
        document.getElementById("notifDetailBody").innerHTML = html;
        document.getElementById("notifDetailTitle").textContent = data.title;
        
        // Mark the notification item as read in the list
        var item = document.querySelector(".notif-item[data-notif-id=\"" + notifId + "\"]");
        if (item) {
            item.classList.remove("list-group-item-warning");
            var btn = item.querySelector(".mark-read-btn");
            if (btn) btn.remove();
        }
        
    } catch (e) {
        document.getElementById("notifDetailBody").innerHTML = 
            "<div class=\"alert alert-danger\">Error loading notification details</div>";
    }
}

async function markSingleRead(notifId, btn) {
    try {
        var formData = new FormData();
        formData.append("action", "mark_read");
        formData.append("id", notifId);
        
        await fetch("notifications.php", { method: "POST", body: formData });
        
        // Update UI
        var item = btn.closest(".notif-item");
        if (item) {
            item.classList.remove("list-group-item-warning");
            btn.remove();
        }
    } catch (e) {
        console.error("Error:", e);
    }
}

async function markAllRead() {
    try {
        var formData = new FormData();
        formData.append("action", "mark_all_read");
        
        await fetch("notifications.php", { method: "POST", body: formData });
        
        // Update UI
        document.querySelectorAll(".notif-item.list-group-item-warning").forEach(function(item) {
            item.classList.remove("list-group-item-warning");
            var btn = item.querySelector(".mark-read-btn");
            if (btn) btn.remove();
        });
        
        showToast("All notifications marked as read", "success");
    } catch (e) {
        showToast("Failed to mark all as read", "error");
    }
}

function notifModalClosed() {
    location.reload();
}

function escapeHtml(str) {
    if (!str) return "";
    var div = document.createElement("div");
    div.textContent = str;
    return div.innerHTML;
}
</script>
';

// Auto-show notification detail if ?view=ID is set
if ($viewNotif): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    viewNotifDetail(<?= $viewNotif['id'] ?>);
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

