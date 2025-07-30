<?php
// Fallback order saving system (without Porter API)
// This file handles order saving when Porter API is unavailable

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection
include_once 'db.php';

// Get input data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Log input
file_put_contents("logs/fallback_input_log.txt", "[" . date("Y-m-d H:i:s") . "] Input: " . $input . "\n", FILE_APPEND);

if (!$data) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid input data"
    ]);
    exit;
}

try {
    // Generate order ID
    $order_id = 'TRIPTI_' . time() . '_' . rand(1000, 9999);
    
    // Extract data
    $customer = $data['customer'];
    $items = $data['items'];
    $payment_id = $data['payment_id'];
    
    // Calculate totals
    $subtotal = $data['subtotal'];
    $gst = $data['gst'];
    $shipping = $data['shipping'];
    $total = $data['total'];
    
    // Save to orders table
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            order_id, payment_id, customer_name, customer_phone, customer_email,
            customer_address, pin_code, subtotal, gst, shipping, total,
            porter_status, payment_status
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Manual', 'Success'
        )
    ");
    
    $stmt->execute([
        $order_id,
        $payment_id,
        $customer['name'],
        $customer['phone'],
        $customer['email'],
        $customer['address'],
        $customer['pinCode'],
        $subtotal,
        $gst,
        $shipping,
        $total
    ]);
    
    // Save order items
    foreach ($items as $item) {
        $stmt = $pdo->prepare("
            INSERT INTO order_items (order_id, item_name, item_price, item_quantity)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $order_id,
            $item['name'],
            $item['price'],
            $item['quantity']
        ]);
    }
    
    // Save customer details
    $stmt = $pdo->prepare("
        INSERT INTO customer_details (order_id, landmark, apartment, latitude, longitude)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $order_id,
        $customer['landmark'] ?? '',
        $customer['apartment'] ?? '',
        floatval($customer['lat'] ?? 0),
        floatval($customer['lng'] ?? 0)
    ]);
    
    // Save payment log
    $stmt = $pdo->prepare("
        INSERT INTO payment_logs (order_id, payment_id, amount, status, gateway_response)
        VALUES (?, ?, ?, 'Success', ?)
    ");
    $stmt->execute([
        $order_id,
        $payment_id,
        $total,
        json_encode(['method' => 'Razorpay', 'status' => 'Success'])
    ]);
    
    // Log system event
    $stmt = $pdo->prepare("
        INSERT INTO system_logs (order_id, log_type, message, data)
        VALUES (?, 'ORDER_CREATED', 'Order created successfully via fallback system', ?)
    ");
    $stmt->execute([
        $order_id,
        json_encode([
            'customer' => $customer,
            'items_count' => count($items),
            'total' => $total,
            'payment_id' => $payment_id
        ])
    ]);
    
    // Success response
    $response = [
        "success" => true,
        "porter_success" => false,
        "message" => "Order saved successfully! Our team will contact you within 30 minutes to arrange delivery.",
        "order_id" => $order_id,
        "fallback_mode" => true,
        "delivery_note" => "Manual delivery arrangement - Porter API unavailable",
        "estimated_contact_time" => "30 minutes",
        "customer_service_phone" => "+919999999999"
    ];
    
    file_put_contents("logs/fallback_success_log.txt", "[" . date("Y-m-d H:i:s") . "] Success: " . json_encode($response, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Error response
    $error_response = [
        "success" => false,
        "message" => "Failed to save order: " . $e->getMessage(),
        "order_id" => $order_id ?? null,
        "fallback_mode" => true
    ];
    
    file_put_contents("logs/fallback_error_log.txt", "[" . date("Y-m-d H:i:s") . "] Error: " . $e->getMessage() . "\nInput: " . $input . "\n", FILE_APPEND);
    
    echo json_encode($error_response);
}
?> 