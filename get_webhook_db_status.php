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

    // Get webhook health status with detailed analysis
    $webhook_health = [
        'razorpay' => [
            'status' => 'active',
            'last_event' => null,
            'total_events' => 0,
            'success_rate' => 100,
            'avg_response_time' => null,
            'last_error' => null
        ],
        'porter' => [
            'status' => 'error',
            'last_event' => null,
            'total_events' => 0,
            'success_rate' => 0,
            'avg_response_time' => null,
            'last_error' => 'HTTP timeout errors detected'
        ]
    ];

    // Check Razorpay webhook events
    $stmt = $pdo->prepare("
        SELECT created_at, COUNT(*) as count
        FROM system_logs 
        WHERE log_type = 'PAYMENT_WEBHOOK'
        GROUP BY DATE(created_at)
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $razorpay_last = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get total Razorpay events
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_count
        FROM system_logs 
        WHERE log_type = 'PAYMENT_WEBHOOK'
    ");
    $stmt->execute();
    $razorpay_total = $stmt->fetch(PDO::FETCH_ASSOC)['total_count'];
    
    if ($razorpay_last) {
        $webhook_health['razorpay']['last_event'] = $razorpay_last['created_at'];
        $webhook_health['razorpay']['total_events'] = $razorpay_total;
        
        // Check if last event was recent (within 7 days)
        $days_since_last = (time() - strtotime($razorpay_last['created_at'])) / (24 * 60 * 60);
        if ($days_since_last > 7) {
            $webhook_health['razorpay']['status'] = 'warning';
        }
    } else {
        $webhook_health['razorpay']['status'] = 'pending';
    }

    // Check Porter webhook events
    $stmt = $pdo->prepare("
        SELECT created_at, COUNT(*) as count
        FROM system_logs 
        WHERE log_type = 'PORTER_WEBHOOK'
        GROUP BY DATE(created_at)
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $porter_last = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get total Porter events
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_count
        FROM system_logs 
        WHERE log_type = 'PORTER_WEBHOOK'
    ");
    $stmt->execute();
    $porter_total = $stmt->fetch(PDO::FETCH_ASSOC)['total_count'];
    
    if ($porter_last) {
        $webhook_health['porter']['status'] = 'active';
        $webhook_health['porter']['last_event'] = $porter_last['created_at'];
        $webhook_health['porter']['total_events'] = $porter_total;
        $webhook_health['porter']['last_error'] = null;
    }

    // Analyze debug logs for error patterns
    $debug_file = "logs/webhook_debug.txt";
    if (file_exists($debug_file)) {
        $debug_content = file_get_contents($debug_file);
        $debug_lines = explode("\n", $debug_content);
        
        $recent_errors = 0;
        $recent_successes = 0;
        $porter_errors = 0;
        $razorpay_errors = 0;
        
        // Analyze last 50 debug entries
        $recent_lines = array_slice($debug_lines, -50);
        foreach ($recent_lines as $line) {
            if (strpos($line, 'failed') !== false || strpos($line, 'ERROR') !== false) {
                $recent_errors++;
                if (strpos($line, 'Porter') !== false) {
                    $porter_errors++;
                }
                if (strpos($line, 'Razorpay') !== false) {
                    $razorpay_errors++;
                }
            }
            if (strpos($line, 'successful') !== false || strpos($line, 'updated') !== false) {
                $recent_successes++;
            }
        }
        
        // Update success rates
        $total_recent = $recent_errors + $recent_successes;
        if ($total_recent > 0) {
            $webhook_health['razorpay']['success_rate'] = round((($recent_successes - $porter_errors) / $total_recent) * 100, 1);
            $webhook_health['porter']['success_rate'] = round((max(0, $recent_successes - $razorpay_errors) / $total_recent) * 100, 1);
        }
        
        // Update Porter status based on recent errors
        if ($porter_errors > 3) {
            $webhook_health['porter']['status'] = 'error';
            $webhook_health['porter']['last_error'] = 'Multiple timeout errors detected in recent tests';
        } else if ($porter_errors > 0) {
            $webhook_health['porter']['status'] = 'warning';
            $webhook_health['porter']['last_error'] = 'Some timeout errors detected';
        }
        
        // Update Razorpay status based on recent errors
        if ($razorpay_errors > 2) {
            $webhook_health['razorpay']['status'] = 'warning';
        }
    }

    // Calculate system health score
    $health_score = 100;
    if ($webhook_health['razorpay']['status'] === 'error') $health_score -= 40;
    else if ($webhook_health['razorpay']['status'] === 'warning') $health_score -= 20;
    else if ($webhook_health['razorpay']['status'] === 'pending') $health_score -= 30;
    
    if ($webhook_health['porter']['status'] === 'error') $health_score -= 40;
    else if ($webhook_health['porter']['status'] === 'warning') $health_score -= 20;
    else if ($webhook_health['porter']['status'] === 'pending') $health_score -= 30;

    echo json_encode([
        'success' => true,
        'total_events' => $total_events,
        'recent_events' => $recent_events,
        'payment_updates' => $payment_updates,
        'delivery_updates' => $delivery_updates,
        'webhook_health' => $webhook_health,
        'system_health_score' => max(0, $health_score),
        'last_updated' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading database status: ' . $e->getMessage()
    ]);
}
?> 