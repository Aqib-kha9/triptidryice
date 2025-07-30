<?php
require_once 'db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON input'
    ]);
    exit;
}

$test_type = $input['type'] ?? '';
$log_file = "logs/webhook_debug.txt";

// Helper function to log
function log_debug($message) {
    global $log_file;
    file_put_contents($log_file, "[" . date("Y-m-d H:i:s") . "] TEST: $message" . PHP_EOL, FILE_APPEND);
}

try {
    if ($test_type === 'razorpay') {
        // Test Razorpay webhook
        $payment_id = $input['payment_id'] ?? 'pay_test_' . time();
        
        $razorpay_payload = [
            "event" => "payment.captured",
            "payload" => [
                "payment" => [
                    "entity" => [
                        "id" => $payment_id,
                        "amount" => 123000,
                        "currency" => "INR",
                        "status" => "captured"
                    ]
                ]
            ]
        ];

        log_debug("Testing Razorpay webhook with payment ID: $payment_id");

        // Call webhook internally
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/webhook.php");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($razorpay_payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "X-Razorpay-Signature: test_signature"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($http_code === 200 && !$curl_error) {
            log_debug("Razorpay webhook test successful");
            echo json_encode([
                'success' => true,
                'message' => 'Razorpay webhook test completed successfully',
                'payment_id' => $payment_id,
                'http_code' => $http_code
            ]);
        } else {
            log_debug("Razorpay webhook test failed: HTTP $http_code, Error: $curl_error");
            echo json_encode([
                'success' => false,
                'message' => "Razorpay webhook test failed: HTTP $http_code",
                'error' => $curl_error,
                'response' => $response
            ]);
        }

    } elseif ($test_type === 'porter') {
        // Test Porter webhook
        $order_id = $input['order_id'] ?? 'PORTER_TEST_' . time();
        
        $porter_payload = [
            "order_id" => $order_id,
            "status" => "Out for Delivery",
            "estimated_delivery_time" => time() * 1000 + 3600000, // 1 hour from now
            "location" => [
                "lat" => 19.0760,
                "lng" => 72.8777
            ],
            "tracking_url" => "https://porter.in/track/$order_id"
        ];

        log_debug("Testing Porter webhook with order ID: $order_id");

        // Call webhook internally
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/webhook.php");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($porter_payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($http_code === 200 && !$curl_error) {
            log_debug("Porter webhook test successful");
            echo json_encode([
                'success' => true,
                'message' => 'Porter webhook test completed successfully',
                'order_id' => $order_id,
                'http_code' => $http_code
            ]);
        } else {
            log_debug("Porter webhook test failed: HTTP $http_code, Error: $curl_error");
            echo json_encode([
                'success' => false,
                'message' => "Porter webhook test failed: HTTP $http_code",
                'error' => $curl_error,
                'response' => $response
            ]);
        }

    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid test type. Use "razorpay" or "porter"'
        ]);
    }

} catch (Exception $e) {
    log_debug("Test error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Test error: ' . $e->getMessage()
    ]);
}
?> 