<?php
/**
 * Duo Brew POS & Self-Ordering System
 * POS API - AJAX endpoints for order creation, payment processing
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = Database::getInstance();
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

switch ($action) {
    case 'create_order':
        handleCreateOrder($db);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Handle order creation
 * Validates input, creates order record, order items, payment,
 * creates notifications
 *
 * Auth handling:
 * - POS orders (order_type='pos') require staff authentication
 * - Self orders (order_type='self') are public (no auth needed)
 */
function handleCreateOrder($db) {
    // Only accept POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    // Get JSON body
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['items']) || !is_array($input['items'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid order data - no items']);
        exit;
    }

    $customerName = trim($input['customer_name'] ?? 'Walk-in Customer');
    $customerPhone = trim($input['customer_phone'] ?? '');
    $orderType = $input['order_type'] ?? 'pos';
    $paymentMethod = $input['payment_method'] ?? 'cash';
    $notes = trim($input['notes'] ?? '');
    $cashReceived = isset($input['cash_received']) ? floatval($input['cash_received']) : null;
    $changeAmount = isset($input['change_amount']) ? floatval($input['change_amount']) : null;
    $items = $input['items'];

    // Validate order type
    if (!in_array($orderType, ['pos', 'self'])) {
        $orderType = 'pos';
    }

    // Validate payment method
    if (!in_array($paymentMethod, ['cash', 'qr'])) {
        $paymentMethod = 'cash';
    }

    // === AUTH CHECK ===
    // POS orders require staff login; self-orders are public
    $isPosOrder = ($orderType === 'pos');
    if ($isPosOrder) {
        // Ensure session is started and user is authenticated as staff
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required for POS orders']);
            exit;
        }
        // Verify staff role (check $_SESSION['role'] directly to avoid header redirect from requireStaff())
        $allowedRoles = ['Administrator', 'Cashier/Staff', 'Cashier'];
        $userRole = $_SESSION['role'] ?? '';
        if (!in_array($userRole, $allowedRoles)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You do not have permission to create POS orders']);
            exit;
        }
    }

    // Calculate totals
    $totalAmount = 0;
    foreach ($items as $item) {
        $qty = intval($item['quantity'] ?? 1);
        $price = floatval($item['unit_price'] ?? 0);
        $subtotal = floatval($item['subtotal'] ?? ($price * $qty));
        $totalAmount += $subtotal;
    }

    if ($totalAmount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Order total must be greater than zero']);
        exit;
    }

    // === DETERMINE PAYMENT STATUS ===
    // POS orders: paid immediately (handled at register)
    // Self orders: unpaid (pay at counter upon pickup)
    $paymentStatus = $isPosOrder ? 'paid' : 'unpaid';
    $paymentRecordStatus = $isPosOrder ? 'completed' : 'pending';

    // Begin transaction
    $db->getConnection()->begin_transaction();

    try {
        // 1. Generate order number
        $orderNumber = generateOrderNumber();

        // 2. Insert order
        $orderSql = "INSERT INTO orders (order_number, customer_name, customer_phone, order_type, status, total_amount, payment_status, notes, created_by)
                     VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, ?)";
        $orderId = $db->insert($orderSql, [
            $orderNumber,
            $customerName,
            $customerPhone,
            $orderType,
            $totalAmount,
            $paymentStatus,
            $notes,
            $_SESSION['user_id'] ?? null
        ], 'ssssdssi');

        if (!$orderId) {
            throw new Exception('Failed to create order');
        }

        // 3. Insert order items
        $itemSql = "INSERT INTO order_items (order_id, product_id, product_name, option_details, quantity, unit_price, subtotal, special_requests)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        foreach ($items as $item) {
            $productId = intval($item['product_id'] ?? 0);
            $productName = trim($item['product_name'] ?? 'Unknown Item');
            $optionDetails = trim($item['option_details'] ?? '');
            $quantity = intval($item['quantity'] ?? 1);
            $unitPrice = floatval($item['unit_price'] ?? 0);
            $subtotal = floatval($item['subtotal'] ?? ($unitPrice * $quantity));
            $specialRequests = trim($item['special_requests'] ?? '');

            if ($quantity <= 0) $quantity = 1;

            $db->insert($itemSql, [
                $orderId,
                $productId,
                $productName,
                $optionDetails,
                $quantity,
                $unitPrice,
                $subtotal,
                $specialRequests
            ], 'iissidds');
        }

        // 4. Insert payment record
        // Handle null cash_received/change_amount for self-order/QR payments
        $cashReceivedVal = $cashReceived !== null ? $cashReceived : 0;
        $changeAmountVal = $changeAmount !== null ? $changeAmount : 0;

        $paymentSql = "INSERT INTO payments (order_id, amount, payment_method, payment_status, cash_received, change_amount)
                       VALUES (?, ?, ?, ?, ?, ?)";
        $db->insert($paymentSql, [
            $orderId,
            $totalAmount,
            $paymentMethod,
            $paymentRecordStatus,
            $cashReceivedVal,
            $changeAmountVal
        ], 'idssdd');

        // 5. Log activity (skip for unauthenticated self-order)
        if (isset($_SESSION['user_id'])) {
            logActivity('Create Order', "Order #{$orderNumber} created - {$paymentMethod} - ₱" . number_format($totalAmount, 2));
        }

        // 6. Create notification for new order
        createNotification('new_order', 'New Order #' . $orderNumber, 'Order from ' . $customerName, $orderId);

        // Commit transaction
        $db->getConnection()->commit();

        // 7. Fetch the created order with items for response
        $order = $db->getRow(
            "SELECT o.*, p.payment_method, p.cash_received, p.change_amount, p.payment_status as pay_status
             FROM orders o
             LEFT JOIN payments p ON p.order_id = o.id
             WHERE o.id = ?",
            [$orderId], 'i'
        );

        $orderItems = $db->getRows(
            "SELECT * FROM order_items WHERE order_id = ?",
            [$orderId], 'i'
        );

        // Success response
        echo json_encode([
            'success' => true,
            'message' => 'Order created successfully',
            'order' => $order,
            'items' => $orderItems
        ]);
        exit;

    } catch (Exception $e) {
        // Rollback on error
        $db->getConnection()->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Error processing order: ' . $e->getMessage()
        ]);
        exit;
    }
}

