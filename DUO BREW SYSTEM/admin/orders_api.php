<?php
/**
 * Duo Brew POS & Self-Ordering System
 * Orders API (AJAX endpoints)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireStaff();

$db = Database::getInstance();
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

switch ($action) {
    case 'get_order':
        $orderId = intval($_GET['id'] ?? 0);
        if ($orderId <= 0) {
            echo json_encode(['error' => 'Invalid order ID']);
            exit;
        }
        
        $order = $db->getRow(
            "SELECT o.*, u.full_name as staff_name FROM orders o LEFT JOIN users u ON o.created_by = u.id WHERE o.id = ?",
            [$orderId], 'i'
        );
        
        if (!$order) {
            echo json_encode(['error' => 'Order not found']);
            exit;
        }
        
        $items = $db->getRows(
            "SELECT * FROM order_items WHERE order_id = ?",
            [$orderId], 'i'
        );
        
        echo json_encode(['order' => $order, 'items' => $items]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

