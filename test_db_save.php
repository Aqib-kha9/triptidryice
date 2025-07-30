<?php
// Test script to verify database saving functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🗄️ Database Save Test</h1>";

// Include database connection
require_once "db.php";

// Test data
$test_data = [
    "payment_id" => "pay_test_" . time(),
    "customer" => [
        "name" => "Test Customer",
        "phone" => "9876543210",
        "email" => "test@example.com",
        "address" => "123 Test Street, Mumbai",
        "pinCode" => "400001",
        "apartment" => "Flat 101",
        "landmark" => "Near Test Landmark",
        "lat" => "19.0760",
        "lng" => "72.8777"
    ],
    "items" => [
        [
            "name" => "Dry Ice Nuggets (5kg)",
            "price" => 250.00,
            "quantity" => 2
        ],
        [
            "name" => "Dry Ice Blocks (10kg)",
            "price" => 500.00,
            "quantity" => 1
        ]
    ],
    "subtotal" => 1000.00,
    "gst" => 180.00,
    "shipping" => 50.00,
    "total" => 1230.00
];

echo "<h2>📊 Test Data:</h2>";
echo "<pre>" . json_encode($test_data, JSON_PRETTY_PRINT) . "</pre>";

// Check if tables exist
echo "<h2>🔍 Database Table Check:</h2>";
$tables = ['orders', 'order_items', 'customer_details', 'porter_tracking', 'payment_logs', 'system_logs'];

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        $status = $exists ? "✅ EXISTS" : "❌ MISSING";
        echo "<p><strong>$table:</strong> $status</p>";
        
        if ($exists) {
            // Show table structure
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<details>";
            echo "<summary>Table Structure</summary>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td>{$col['Field']}</td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "<td>{$col['Default']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "</details>";
        }
    } catch (Exception $e) {
        echo "<p><strong>$table:</strong> ❌ ERROR - " . $e->getMessage() . "</p>";
    }
}

// Test saving data
echo "<h2>💾 Test Data Saving:</h2>";

try {
    // Generate order ID
    $order_id = 'TEST_' . time() . '_' . rand(1000, 9999);
    $created_at = date('Y-m-d H:i:s');
    
    echo "<h3>1. Saving to orders table...</h3>";
    
    // Save to orders table
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            order_id, payment_id, customer_name, customer_phone, customer_email,
            customer_address, pin_code, subtotal, gst, shipping, total, created_at
        ) VALUES (
            :order_id, :payment_id, :name, :phone, :email,
            :address, :pin, :subtotal, :gst, :shipping, :total, :created_at
        )
    ");

    $stmt->execute([
        ":order_id" => $order_id,
        ":payment_id" => $test_data['payment_id'],
        ":name" => $test_data['customer']['name'],
        ":phone" => $test_data['customer']['phone'],
        ":email" => $test_data['customer']['email'],
        ":address" => $test_data['customer']['address'],
        ":pin" => $test_data['customer']['pinCode'],
        ":subtotal" => $test_data['subtotal'],
        ":gst" => $test_data['gst'],
        ":shipping" => $test_data['shipping'],
        ":total" => $test_data['total'],
        ":created_at" => $created_at
    ]);
    
    echo "<p style='color: green;'>✅ Orders table - Data saved successfully!</p>";
    
    echo "<h3>2. Saving to order_items table...</h3>";
    
    // Save order items
    foreach ($test_data['items'] as $item) {
        $stmt_items = $pdo->prepare("
            INSERT INTO order_items (
                order_id, item_name, item_price, item_quantity
            ) VALUES (
                :order_id, :item_name, :item_price, :item_quantity
            )
        ");

        $stmt_items->execute([
            ":order_id" => $order_id,
            ":item_name" => $item['name'],
            ":item_price" => $item['price'],
            ":item_quantity" => $item['quantity']
        ]);
    }
    
    echo "<p style='color: green;'>✅ Order_items table - Data saved successfully!</p>";
    
    echo "<h3>3. Saving to customer_details table...</h3>";
    
    // Save customer details
    $stmt_customer = $pdo->prepare("
        INSERT INTO customer_details (
            order_id, landmark, apartment, latitude, longitude
        ) VALUES (
            :order_id, :landmark, :apartment, :latitude, :longitude
        )
    ");
    
    $stmt_customer->execute([
        ":order_id" => $order_id,
        ":landmark" => $test_data['customer']['landmark'],
        ":apartment" => $test_data['customer']['apartment'],
        ":latitude" => floatval($test_data['customer']['lat']),
        ":longitude" => floatval($test_data['customer']['lng'])
    ]);
    
    echo "<p style='color: green;'>✅ Customer_details table - Data saved successfully!</p>";
    
    echo "<h3>4. Saving to payment_logs table...</h3>";
    
    // Save payment log
    $stmt_payment = $pdo->prepare("
        INSERT INTO payment_logs (
            order_id, payment_id, amount, status, gateway_response
        ) VALUES (
            :order_id, :payment_id, :amount, 'Success', :gateway_response
        )
    ");
    
    $stmt_payment->execute([
        ":order_id" => $order_id,
        ":payment_id" => $test_data['payment_id'],
        ":amount" => $test_data['total'],
        ":gateway_response" => json_encode(['method' => 'Razorpay', 'status' => 'Success'])
    ]);
    
    echo "<p style='color: green;'>✅ Payment_logs table - Data saved successfully!</p>";
    
    echo "<h3>5. Saving to system_logs table...</h3>";
    
    // Save system log
    $stmt_system = $pdo->prepare("
        INSERT INTO system_logs (
            order_id, log_type, message, data
        ) VALUES (
            :order_id, 'TEST_ORDER', 'Test order created successfully', :data
        )
    ");
    
    $stmt_system->execute([
        ":order_id" => $order_id,
        ":data" => json_encode([
            'customer' => $test_data['customer'],
            'items_count' => count($test_data['items']),
            'total' => $test_data['total'],
            'payment_id' => $test_data['payment_id']
        ])
    ]);
    
    echo "<p style='color: green;'>✅ System_logs table - Data saved successfully!</p>";
    
    // Verify saved data
    echo "<h2>🔍 Verification - Checking Saved Data:</h2>";
    
    // Check orders table
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        echo "<h3>✅ Orders Table Data:</h3>";
        echo "<pre>" . json_encode($order, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p style='color: red;'>❌ No data found in orders table!</p>";
    }
    
    // Check order_items table
    $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($items) {
        echo "<h3>✅ Order Items Table Data:</h3>";
        echo "<pre>" . json_encode($items, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p style='color: red;'>❌ No data found in order_items table!</p>";
    }
    
    // Check customer_details table
    $stmt = $pdo->prepare("SELECT * FROM customer_details WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer) {
        echo "<h3>✅ Customer Details Table Data:</h3>";
        echo "<pre>" . json_encode($customer, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p style='color: red;'>❌ No data found in customer_details table!</p>";
    }
    
    // Check payment_logs table
    $stmt = $pdo->prepare("SELECT * FROM payment_logs WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($payment) {
        echo "<h3>✅ Payment Logs Table Data:</h3>";
        echo "<pre>" . json_encode($payment, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p style='color: red;'>❌ No data found in payment_logs table!</p>";
    }
    
    // Check system_logs table
    $stmt = $pdo->prepare("SELECT * FROM system_logs WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $system = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($system) {
        echo "<h3>✅ System Logs Table Data:</h3>";
        echo "<pre>" . json_encode($system, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p style='color: red;'>❌ No data found in system_logs table!</p>";
    }
    
    echo "<h2>🎉 Summary:</h2>";
    echo "<p style='color: green; font-weight: bold;'>✅ All data saved successfully to database!</p>";
    echo "<p><strong>Order ID:</strong> $order_id</p>";
    echo "<p><strong>Payment ID:</strong> {$test_data['payment_id']}</p>";
    echo "<p><strong>Customer:</strong> {$test_data['customer']['name']}</p>";
    echo "<p><strong>Total Amount:</strong> ₹{$test_data['total']}</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Stack Trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Show recent orders
echo "<h2>📋 Recent Orders in Database:</h2>";
try {
    $stmt = $pdo->query("
        SELECT o.order_id, o.customer_name, o.total, o.created_at, 
               COUNT(oi.id) as item_count
        FROM orders o
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        GROUP BY o.order_id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($recent_orders) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Order ID</th><th>Customer</th><th>Total</th><th>Items</th><th>Created</th></tr>";
        foreach ($recent_orders as $order) {
            echo "<tr>";
            echo "<td>{$order['order_id']}</td>";
            echo "<td>{$order['customer_name']}</td>";
            echo "<td>₹{$order['total']}</td>";
            echo "<td>{$order['item_count']}</td>";
            echo "<td>{$order['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No orders found in database.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error fetching recent orders: " . $e->getMessage() . "</p>";
}
?> 