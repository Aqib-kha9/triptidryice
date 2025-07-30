<?php
require_once 'db.php';

date_default_timezone_set('Asia/Kolkata');
$logFile = "logs/webhook_debug.txt";

// Helper: log anything
function log_debug($message) {
    global $logFile;
    file_put_contents($logFile, "[" . date("Y-m-d H:i:s") . "] $message" . PHP_EOL, FILE_APPEND);
}

try {
    $data = file_get_contents("php://input");
    log_debug("RAW INPUT: " . $data);

    $headers = getallheaders();
    log_debug("HEADERS: " . json_encode($headers));

    $payload = json_decode($data, true);
    if (!$payload) {
        throw new Exception("Invalid JSON received");
    }

    // Razorpay Webhook
    if (isset($headers['X-Razorpay-Signature'])) {
        log_debug("Processing Razorpay Webhook");
        
        $payment_id = $payload['payload']['payment']['entity']['id'] ?? '';
        $status = $payload['event'] ?? '';
        $amount = $payload['payload']['payment']['entity']['amount'] ?? 0;
        
        log_debug("Razorpay Webhook: Payment ID = $payment_id | Status = $status | Amount = $amount");

        // Update payment status in orders table
        if ($payment_id) {
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET payment_status = ?, updated_at = NOW() 
                WHERE payment_id = ?
            ");
            $stmt->execute([$status, $payment_id]);
            
            // Log payment event
            $stmt = $pdo->prepare("
                INSERT INTO system_logs (order_id, log_type, message, data) 
                SELECT order_id, 'PAYMENT_WEBHOOK', ?, ? 
                FROM orders WHERE payment_id = ?
            ");
            $stmt->execute([
                "Payment webhook received: $status",
                json_encode($payload),
                $payment_id
            ]);
            
            log_debug("Payment status updated for Payment ID: $payment_id");
        }

        http_response_code(200);
        echo json_encode(["message" => "Razorpay webhook processed successfully"]);
        exit;
    }

    // Porter Webhook
    else if (isset($payload['order_id']) && isset($payload['status'])) {
        log_debug("Processing Porter Webhook");
        
        $porter_order_id = $payload['order_id'];
        $status = $payload['status'];
        $event_time = date("Y-m-d H:i:s");

        $eta_ts = $payload['estimated_delivery_time'] ?? null;
        $lat = $payload['location']['lat'] ?? null;
        $lng = $payload['location']['lng'] ?? null;
        $tracking_url = $payload['tracking_url'] ?? null;

        log_debug("Porter Webhook: Order ID = $porter_order_id | Status = $status | ETA = $eta_ts");

        // Find our order by porter_order_id
        $stmt = $pdo->prepare("
            SELECT order_id FROM orders WHERE porter_order_id = ?
        ");
        $stmt->execute([$porter_order_id]);
        $our_order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($our_order) {
            $order_id = $our_order['order_id'];
            
            // Update orders table
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET porter_status = ?, updated_at = NOW() 
                WHERE order_id = ?
            ");
            $stmt->execute([$status, $order_id]);

            // Update or insert porter_tracking
            $stmt = $pdo->prepare("
                INSERT INTO porter_tracking (
                    order_id, porter_order_id, status, tracking_url, 
                    estimated_delivery_time, delivery_lat, delivery_lng, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    status = VALUES(status),
                    tracking_url = VALUES(tracking_url),
                    estimated_delivery_time = VALUES(estimated_delivery_time),
                    delivery_lat = VALUES(delivery_lat),
                    delivery_lng = VALUES(delivery_lng),
                    updated_at = NOW()
            ");
            
            $estimated_delivery = $eta_ts ? date("Y-m-d H:i:s", $eta_ts / 1000) : null;
            $stmt->execute([
                $order_id,
                $porter_order_id,
                $status,
                $tracking_url,
                $estimated_delivery,
                $lat,
                $lng
            ]);

            // Log tracking event
            $stmt = $pdo->prepare("
                INSERT INTO system_logs (order_id, log_type, message, data) 
                VALUES (?, 'PORTER_WEBHOOK', ?, ?)
            ");
            $stmt->execute([
                $order_id,
                "Porter status updated: $status",
                json_encode($payload)
            ]);

            log_debug("Porter Data Saved Successfully for Order: $order_id");
        } else {
            log_debug("Order not found for Porter Order ID: $porter_order_id");
        }

        http_response_code(200);
        echo json_encode(["message" => "Porter webhook processed successfully"]);
        exit;
    }

    // Unknown Source
    else {
        log_debug("Unknown webhook structure");
        http_response_code(400);
        echo json_encode(["error" => "Unknown webhook source"]);
        exit;
    }

} catch (PDOException $e) {
    log_debug("DB ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Database error"]);
} catch (Exception $e) {
    log_debug("GENERAL ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Internal server error"]);
}
?>
