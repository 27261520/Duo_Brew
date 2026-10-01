<?php
/**
 * Duo Brew POS & Self-Ordering System
 * Products API (AJAX endpoints for product options)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = Database::getInstance();
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

switch ($action) {
    case 'get_options':
        // Public endpoint - no auth required (used by self-ordering)
        $productId = intval($_GET['product_id'] ?? 0);
        if ($productId <= 0) {
            echo json_encode([]);
            exit;
        }
        
        $options = $db->getRows(
            "SELECT * FROM product_options WHERE product_id = ? OR (product_id IS NULL AND option_type = 'addon') ORDER BY option_type, id",
            [$productId], 'i'
        );
        echo json_encode($options);
        break;
        
    case 'get_products':
        $categoryId = intval($_GET['category_id'] ?? 0);
        $search = $_GET['search'] ?? '';
        
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                JOIN categories c ON p.category_id = c.id 
                WHERE p.status = 'active'";
        $params = [];
        $types = '';
        
        if ($categoryId > 0) {
            $sql .= " AND p.category_id = ?";
            $params[] = $categoryId;
            $types .= 'i';
        }
        
        if (!empty($search)) {
            $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $types .= 'ss';
        }
        
        $sql .= " ORDER BY c.sort_order ASC, p.name ASC";
        
        $products = $db->getRows($sql, $params, $types);
        echo json_encode($products);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

