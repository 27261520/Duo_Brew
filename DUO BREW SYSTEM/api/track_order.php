<?php
/**
 * Duo Brew POS & Self-Ordering System
 * Track Order API (Public)
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$orderNumber = $_GET['order_number'] ?? '';

if (empty($orderNumber)) {
    echo json_encode(['found' => false, 'message' => 'Order number required']);
    exit;
}

$order = $db->getRow(
    "SELECT * FROM orders WHERE order_number = ?",
    [$orderNumber], 's'
);

if (!$order) {
    echo json_encode(['found' => false, 'message' => 'Order not found']);
    exit;
}

$items = $db->getRows(
    "SELECT * FROM order_items WHERE order_id = ?",
    [$order['id']], 'i'
);

echo json_encode([
    'found' => true,
    'order' => $order,
    'items' => $items
]);

