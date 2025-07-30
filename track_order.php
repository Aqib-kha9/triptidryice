<?php
$conn = new mysqli("localhost", "root", "", "tripti_orders");
$order_id = $_GET['id'] ?? '';

if (!$order_id) {
    die("❌ Please enter a valid Order ID.");
}

$stmt = $conn->prepare("SELECT porter_status, delivery_time, porter_tracking_data FROM orders WHERE porter_order_id = ? OR order_id = ?");
$stmt->bind_param("ss", $order_id, $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("❌ No tracking data found for ID: $order_id");
}

$row = $result->fetch_assoc();
$data = json_decode($row['porter_tracking_data'], true);

echo "<h2>📦 Porter Order Tracking</h2>";
echo "<strong>Status:</strong> " . htmlspecialchars($row['porter_status']) . "<br>";
echo "<strong>Delivered At:</strong> " . ($row['delivery_time'] ?? 'Pending') . "<br><br>";

if ($data) {
    echo "<h3>Tracking Details:</h3>";
    echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
} else {
    echo "No detailed tracking info available.";
}
?>
