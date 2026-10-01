<?php
/**
 * Duo Brew POS & Self-Ordering System
 * Dashboard API (AJAX refresh)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireStaff();
header('Content-Type: application/json');

$db = Database::getInstance();

$today = getTodayDateRange();

// Stats
$todaySales = $db->getValue(
    "SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE created_at BETWEEN ? AND ? AND payment_status = 'paid'",
    [$today['start'], $today['end']], 'ss'
);

$totalOrders = $db->getValue(
    "SELECT COUNT(*) FROM orders WHERE created_at BETWEEN ? AND ?",
    [$today['start'], $today['end']], 'ss'
);

$pendingOrders = $db->getValue("SELECT COUNT(*) FROM orders WHERE status = 'pending'", [], '');
$preparingOrders = $db->getValue("SELECT COUNT(*) FROM orders WHERE status = 'preparing'", [], '');
$readyOrders = $db->getValue("SELECT COUNT(*) FROM orders WHERE status = 'ready'", [], '');
$completedOrders = $db->getValue(
    "SELECT COUNT(*) FROM orders WHERE status = 'completed' AND created_at BETWEEN ? AND ?",
    [$today['start'], $today['end']], 'ss'
);
$lowStockCount = $db->getValue("SELECT COUNT(*) FROM inventory WHERE quantity <= low_stock_threshold", [], '');

// Recent orders
$recentOrders = $db->getRows(
    "SELECT o.*, u.full_name as staff_name 
     FROM orders o 
     LEFT JOIN users u ON o.created_by = u.id 
     ORDER BY o.created_at DESC 
     LIMIT 10", [], ''
);

echo json_encode([
    'success' => true,
    'stats' => [
        'todaySales' => '₱' . number_format($todaySales, 2),
        'totalOrders' => $totalOrders,
        'pending' => $pendingOrders,
        'preparing' => $preparingOrders,
        'ready' => $readyOrders,
        'completed' => $completedOrders,
        'lowStock' => $lowStockCount
    ],
    'recentOrders' => array_map(function($o) {
        return [
            'order_number' => $o['order_number'],
            'customer_name' => $o['customer_name'],
            'status_badge' => getStatusBadge($o['status']),
            'status_label' => getStatusLabel($o['status']),
            'total_amount' => $o['total_amount'],
            'created_at' => $o['created_at']
        ];
    }, $recentOrders)
]);

