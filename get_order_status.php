<?php
require_once "db.php";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Get order ID from request
$order_id = $_GET['order_id'] ?? $_POST['order_id'] ?? null;

// Handle JSON input
if (!$order_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $order_id = $input['order_id'] ?? null;
}

if (!$order_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Order ID is required'
    ]);
    exit;
}

// Clean and normalize order ID
$order_id = trim($order_id);

try {
    // Get order details with flexible matching
    $stmt = $pdo->prepare("
        SELECT o.*, 
               pt.tracking_url,
               pt.estimated_pickup_time,
               pt.estimated_delivery_time,
               pt.delivery_charge as porter_charge,
               pt.currency
        FROM orders o
        LEFT JOIN porter_tracking pt ON o.order_id = pt.order_id
        WHERE o.order_id = ? OR o.order_id LIKE ?
    ");
    
    // Try exact match first, then partial match
    $stmt->execute([$order_id, "%$order_id%"]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode([
            'success' => false,
            'message' => 'Order not found. Please check your Order ID.'
        ]);
        exit;
    }

    // Get order items
    $stmt_items = $pdo->prepare("
        SELECT item_name, item_price, item_quantity
        FROM order_items 
        WHERE order_id = ?
    ");
    $stmt_items->execute([$order_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // Prepare response in the format expected by frontend
    $response = [
        'success' => true,
        'order' => [
            'order_id' => $order['order_id'],
            'payment_id' => $order['payment_id'],
            'customer_name' => $order['customer_name'],
            'customer_phone' => $order['customer_phone'],
            'customer_email' => $order['customer_email'],
            'customer_address' => $order['customer_address'],
            'pin_code' => $order['pin_code'],
            'items' => $items,
            'subtotal' => floatval($order['subtotal']),
            'gst' => floatval($order['gst']),
            'shipping' => floatval($order['shipping']),
            'total' => floatval($order['total']),
            'payment_status' => $order['payment_status'],
            'porter_status' => $order['porter_status'] ?? 'Pending',
            'porter_order_id' => $order['porter_order_id'],
            'porter_tracking_url' => $order['tracking_url'],
            'estimated_pickup_time' => $order['estimated_pickup_time'],
            'estimated_delivery_time' => $order['estimated_delivery_time'],
            'delivery_charge' => $order['porter_charge'],
            'currency' => $order['currency'] ?? 'INR',
            'created_at' => $order['created_at'],
            'updated_at' => $order['updated_at'],
            'current_step' => 2 // Default step for timeline
        ]
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again.',
        'details' => $e->getMessage()
    ]);
}
?> 