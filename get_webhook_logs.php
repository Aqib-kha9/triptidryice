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
        $formatted_logs[] = [
            'timestamp' => $log['created_at'],
            'type' => $log['log_type'] === 'PAYMENT_WEBHOOK' ? 'success' : 'info',
            'message' => $log['message'],
            'details' => $data ? json_encode($data, JSON_PRETTY_PRINT) : 'No additional data'
        ];
    }

    // Also check webhook debug file
    $debug_file = "logs/webhook_debug.txt";
    if (file_exists($debug_file)) {
        $debug_content = file_get_contents($debug_file);
        $debug_lines = array_slice(explode("\n", $debug_content), -10); // Last 10 lines
        
        foreach ($debug_lines as $line) {
            if (trim($line)) {
                $formatted_logs[] = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'type' => 'debug',
                    'message' => 'Webhook Debug Log',
                    'details' => trim($line)
                ];
            }
        }
    }

    echo json_encode([
        'success' => true,
        'logs' => $formatted_logs
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading webhook logs: ' . $e->getMessage()
    ]);
}
?> 