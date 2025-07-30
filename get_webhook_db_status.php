<?php
require_once 'db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Get total webhook events
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_events
        FROM system_logs 
        WHERE log_type IN ('PAYMENT_WEBHOOK', 'PORTER_WEBHOOK')
    ");
    $stmt->execute();
    $total_events = $stmt->fetch(PDO::FETCH_ASSOC)['total_events'];

    // Get recent events (last 24 hours)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as recent_events
        FROM system_logs 
        WHERE log_type IN ('PAYMENT_WEBHOOK', 'PORTER_WEBHOOK')
        AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute();
    $recent_events = $stmt->fetch(PDO::FETCH_ASSOC)['recent_events'];

    // Get payment status updates
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as payment_updates
        FROM orders 
        WHERE payment_status != 'Pending'
        AND updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute();
    $payment_updates = $stmt->fetch(PDO::FETCH_ASSOC)['payment_updates'];

    // Get delivery status updates
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as delivery_updates
        FROM orders 
        WHERE porter_status != 'Pending'
        AND updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute();
    $delivery_updates = $stmt->fetch(PDO::FETCH_ASSOC)['delivery_updates'];

    // Get webhook health status
    $webhook_health = [
        'razorpay' => [
            'status' => 'active',
            'last_event' => null,
            'total_events' => 0
        ],
        'porter' => [
            'status' => 'pending',
            'last_event' => null,
            'total_events' => 0
        ]
    ];

    // Check last Razorpay webhook event
    $stmt = $pdo->prepare("
        SELECT created_at, COUNT(*) as count
        FROM system_logs 
        WHERE log_type = 'PAYMENT_WEBHOOK'
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $razorpay_last = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($razorpay_last) {
        $webhook_health['razorpay']['last_event'] = $razorpay_last['created_at'];
        $webhook_health['razorpay']['total_events'] = $razorpay_last['count'];
    }

    // Check last Porter webhook event
    $stmt = $pdo->prepare("
        SELECT created_at, COUNT(*) as count
        FROM system_logs 
        WHERE log_type = 'PORTER_WEBHOOK'
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $porter_last = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($porter_last) {
        $webhook_health['porter']['status'] = 'active';
        $webhook_health['porter']['last_event'] = $porter_last['created_at'];
        $webhook_health['porter']['total_events'] = $porter_last['count'];
    }

    echo json_encode([
        'success' => true,
        'total_events' => $total_events,
        'recent_events' => $recent_events,
        'payment_updates' => $payment_updates,
        'delivery_updates' => $delivery_updates,
        'webhook_health' => $webhook_health
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading database status: ' . $e->getMessage()
    ]);
}
?> 