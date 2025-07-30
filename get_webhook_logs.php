<?php
require_once 'db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Get recent webhook logs from system_logs table
    $stmt = $pdo->prepare("
        SELECT order_id, log_type, message, data, created_at
        FROM system_logs 
        WHERE log_type IN ('PAYMENT_WEBHOOK', 'PORTER_WEBHOOK')
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format logs for frontend
    $formatted_logs = [];
    foreach ($logs as $log) {
        $data = json_decode($log['data'], true);
        $type = $log['log_type'] === 'PAYMENT_WEBHOOK' ? 'success' : 'info';
        
        // Format the details based on webhook type
        $details = '';
        if ($data) {
            if ($log['log_type'] === 'PAYMENT_WEBHOOK') {
                $payment_id = $data['payload']['payment']['entity']['id'] ?? 'Unknown';
                $amount = $data['payload']['payment']['entity']['amount'] ?? 0;
                $currency = $data['payload']['payment']['entity']['currency'] ?? 'INR';
                $details = "Payment ID: $payment_id | Amount: " . ($amount/100) . " $currency";
            } else if ($log['log_type'] === 'PORTER_WEBHOOK') {
                $order_id = $data['order_id'] ?? 'Unknown';
                $status = $data['status'] ?? 'Unknown';
                $details = "Porter Order: $order_id | Status: $status";
            }
        }
        
        $formatted_logs[] = [
            'timestamp' => $log['created_at'],
            'type' => $type,
            'message' => $log['message'],
            'details' => $details ?: json_encode($data, JSON_PRETTY_PRINT)
        ];
    }

    // Also parse webhook debug file for more recent activity
    $debug_file = "logs/webhook_debug.txt";
    if (file_exists($debug_file)) {
        $debug_content = file_get_contents($debug_file);
        $debug_lines = array_slice(explode("\n", trim($debug_content)), -15); // Last 15 lines
        
        foreach ($debug_lines as $line) {
            if (trim($line)) {
                $timestamp = date('Y-m-d H:i:s');
                $type = 'debug';
                $message = 'Webhook Debug Log';
                $details = trim($line);
                
                // Extract timestamp from debug line if present
                if (preg_match('/\[([^\]]+)\]/', $line, $matches)) {
                    $timestamp = $matches[1];
                    $details = trim(str_replace($matches[0], '', $line));
                }
                
                // Categorize debug messages
                if (strpos($details, 'ERROR') !== false || strpos($details, 'failed') !== false) {
                    $type = 'error';
                    $message = 'Webhook Error';
                } else if (strpos($details, 'successful') !== false || strpos($details, 'updated') !== false) {
                    $type = 'success';
                    $message = 'Webhook Success';
                } else if (strpos($details, 'Processing') !== false) {
                    $type = 'info';
                    $message = 'Webhook Processing';
                } else if (strpos($details, 'TEST:') !== false) {
                    $type = 'debug';
                    $message = 'Webhook Test';
                }
                
                $formatted_logs[] = [
                    'timestamp' => $timestamp,
                    'type' => $type,
                    'message' => $message,
                    'details' => $details
                ];
            }
        }
    }

    // Sort all logs by timestamp (newest first)
    usort($formatted_logs, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });

    // Limit to 30 most recent logs
    $formatted_logs = array_slice($formatted_logs, 0, 30);

    echo json_encode([
        'success' => true,
        'logs' => $formatted_logs,
        'total_logs' => count($formatted_logs)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading webhook logs: ' . $e->getMessage()
    ]);
}
?> 