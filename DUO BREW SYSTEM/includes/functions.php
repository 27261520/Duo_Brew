<?php
/**
 * Duo Brew POS & Self-Ordering System
 * Utility Functions
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Universal safe redirect function
 */
function redirect($url) {
    if (!headers_sent()) {
        header('Location: ' . $url);
    } else {
        echo "<script>window.location.href='" . $url . "';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=" . $url . "'></noscript>";
    }
    exit;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user has specific role
 */
function hasRole($roleName) {
    if (!isset($_SESSION['role'])) return false;
    return strtolower($_SESSION['role']) === strtolower($roleName);
}

/**
 * Check if user is a Cashier
 */
function isCashier() {
    return hasRole('Cashier');
}

/**
 * Check if user is Staff (not Cashier)
 */
function isStaff() {
    return hasRole('Cashier/Staff') || hasRole('Staff');
}

/**
 * Check if user has any of the specified roles
 */
function hasAnyRole($roles) {
    foreach ($roles as $role) {
        if (hasRole($role)) return true;
    }
    return false;
}

/**
 * Require authentication - redirect to login if not authenticated
 */
function requireAuth() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        redirect(BASE_URL . 'auth/login.php');
    }
}

/**
 * Require specific role(s)
 */
function requireRole($roles) {
    requireAuth();
    if (!hasAnyRole((array)$roles)) {
        $_SESSION['error'] = 'You do not have permission to access this page.';
        redirect(BASE_URL . 'index.php');
    }
}

/**
 * Require admin role
 */
function requireAdmin() {
    requireRole('Administrator');
}

/**
 * Require staff role (Admin, Cashier/Staff, or Cashier)
 */
function requireStaff() {
    requireAuth();
    if (!hasAnyRole(['Administrator', 'Cashier/Staff', 'Cashier'])) {
        $_SESSION['error'] = 'You do not have permission to access this page.';
        redirect(BASE_URL . 'index.php');
    }
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return CURRENCY_SYMBOL . number_format($amount, 2);
}

/**
 * Format date
 */
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime($datetime, $format = 'M d, Y h:i A') {
    return date($format, strtotime($datetime));
}

/**
 * Generate order number
 */
function generateOrderNumber() {
    $db = Database::getInstance();
    $date = date('Ymd');
    $lastOrder = $db->getValue(
        "SELECT order_number FROM orders WHERE order_number LIKE ? ORDER BY id DESC LIMIT 1",
        [ORDER_PREFIX . $date . '%'],
        's'
    );
    
    if ($lastOrder) {
        $lastNum = intval(substr($lastOrder, -4));
        $newNum = $lastNum + 1;
    } else {
        $newNum = 1;
    }
    
    return ORDER_PREFIX . $date . str_pad($newNum, 4, '0', STR_PAD_LEFT);
}

/**
 * Log user activity
 */
function logActivity($action, $details = null) {
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    $db->insert(
        "INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)",
        [$userId, $action, $details, $ip],
        'isss'
    );
}

/**
 * Create notification
 */
function createNotification($type, $title, $message = null, $relatedId = null) {
    $db = Database::getInstance();
    return $db->insert(
        "INSERT INTO notifications (type, title, message, related_id) VALUES (?, ?, ?, ?)",
        [$type, $title, $message, $relatedId],
        'sssi'
    );
}

/**
 * Get unread notification count
 */
function getUnreadNotificationCount() {
    $db = Database::getInstance();
    return $db->getValue(
        "SELECT COUNT(*) FROM notifications WHERE is_read = FALSE"
    );
}

/**
 * Get recent notifications
 */
function getRecentNotifications($limit = 10) {
    $db = Database::getInstance();
    return $db->getRows(
        "SELECT * FROM notifications ORDER BY created_at DESC LIMIT ?",
        [$limit],
        'i'
    );
}

/**
 * Mark notification as read
 */
function markNotificationRead($id) {
    $db = Database::getInstance();
    return $db->execute(
        "UPDATE notifications SET is_read = TRUE WHERE id = ?",
        [$id],
        'i'
    );
}

/**
 * Get notification with related order data
 */
function getNotificationWithOrder($notificationId) {
    $db = Database::getInstance();
    $notif = $db->getRow(
        "SELECT * FROM notifications WHERE id = ?",
        [$notificationId], 'i'
    );
    
    if (!$notif) return null;
    
    if ($notif['related_id'] && in_array($notif['type'], ['new_order', 'payment'])) {
        $order = $db->getRow(
            "SELECT o.*, u.full_name as staff_name 
             FROM orders o 
             LEFT JOIN users u ON o.created_by = u.id 
             WHERE o.id = ?",
            [$notif['related_id']], 'i'
        );
        
        if ($order) {
            $items = $db->getRows(
                "SELECT * FROM order_items WHERE order_id = ?",
                [$notif['related_id']], 'i'
            );
            $order['items'] = $items;
            $notif['order'] = $order;
        }
    }
    
    return $notif;
}

/**
 * Get status badge class
 */
function getStatusBadge($status) {
    $badges = [
        'pending' => 'bg-warning text-dark',
        'preparing' => 'bg-info text-white',
        'ready' => 'bg-success text-white',
        'completed' => 'bg-secondary text-white',
        'cancelled' => 'bg-danger text-white',
        'paid' => 'bg-success text-white',
        'unpaid' => 'bg-danger text-white',
        'refunded' => 'bg-warning text-dark',
        'active' => 'bg-success text-white',
        'inactive' => 'bg-secondary text-white',
        'archived' => 'bg-dark text-white'
    ];
    
    return $badges[strtolower($status)] ?? 'bg-primary text-white';
}

/**
 * Get status label
 */
function getStatusLabel($status) {
    $labels = [
        'pending' => 'Pending',
        'preparing' => 'Preparing',
        'ready' => 'Ready for Pickup',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'paid' => 'Paid',
        'unpaid' => 'Unpaid',
        'refunded' => 'Refunded',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'archived' => 'Archived'
    ];
    
    return $labels[strtolower($status)] ?? ucfirst($status);
}

/**
 * Sanitize input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get time ago string
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff/60) . ' min ago';
    if ($diff < 86400) return floor($diff/3600) . ' hr ago';
    if ($diff < 604800) return floor($diff/86400) . ' days ago';
    
    return formatDate($datetime);
}

/**
 * Calculate percentage change
 */
function calculatePercentageChange($current, $previous) {
    if ($previous == 0) {
        return $current > 0 ? 100 : 0;
    }
    return round((($current - $previous) / $previous) * 100, 1);
}

/**
 * Get today's date range
 */
function getTodayDateRange() {
    return [
        'start' => date('Y-m-d 00:00:00'),
        'end' => date('Y-m-d 23:59:59')
    ];
}

/**
 * Get current month date range
 */
function getMonthDateRange() {
    return [
        'start' => date('Y-m-01 00:00:00'),
        'end' => date('Y-m-t 23:59:59')
    ];
}

/**
 * Get current week date range (Monday to Sunday)
 */
function getWeekDateRange() {
    $monday = date('Y-m-d', strtotime('monday this week'));
    $sunday = date('Y-m-d', strtotime('sunday this week'));
    return [
        'start' => $monday . ' 00:00:00',
        'end' => $sunday . ' 23:59:59'
    ];
}

/**
 * Redirect with message
 */
function redirectWith($url, $message, $type = 'success') {
    $_SESSION[$type] = $message;
    redirect($url);
}

/**
 * Display Bootstrap alert
 */
function displayAlert($message, $type = 'success') {
    return "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>";
}

/**
 * Show flash messages
 */
function showFlashMessages() {
    $types = ['success', 'error', 'warning', 'info'];
    $output = '';
    
    foreach ($types as $type) {
        if (isset($_SESSION[$type])) {
            $output .= displayAlert($_SESSION[$type], $type === 'error' ? 'danger' : $type);
            unset($_SESSION[$type]);
        }
    }
    
    return $output;
}

/**
 * Get sales data for a date range
 */
function getSalesData($startDate, $endDate) {
    $db = Database::getInstance();
    return $db->getRow(
        "SELECT 
            COUNT(*) as total_orders,
            COALESCE(SUM(total_amount), 0) as total_sales,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
            COUNT(CASE WHEN status = 'preparing' THEN 1 END) as preparing_orders,
            COUNT(CASE WHEN status = 'ready' THEN 1 END) as ready_orders,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders
        FROM orders 
        WHERE created_at BETWEEN ? AND ?
        AND payment_status = 'paid'",
        [$startDate, $endDate],
        'ss'
    );
}

/**
 * Get best selling products
 */
function getBestSellingProducts($limit = 5, $startDate = null, $endDate = null) {
    $db = Database::getInstance();
    $sql = "SELECT 
                oi.product_id,
                oi.product_name,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.subtotal) as total_revenue
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE o.payment_status = 'paid'";
    
    $params = [];
    $types = '';
    
    if ($startDate && $endDate) {
        $sql .= " AND o.created_at BETWEEN ? AND ?";
        $params = [$startDate, $endDate];
        $types = 'ss';
    }
    
    $sql .= " GROUP BY oi.product_id, oi.product_name 
              ORDER BY total_quantity DESC 
              LIMIT ?";
    
    $params[] = $limit;
    $types .= 'i';
    
    return $db->getRows($sql, $params, $types);
}