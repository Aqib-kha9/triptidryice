<?php
require 'db.php';

$order_id = $_GET['order_id'] ?? '';
if (!$order_id) {
    echo json_encode(["error" => "Order ID missing"]);
    exit;
}

$stmt = $pdo->prepare("SELECT porter_status, porter_eta, delivery_lat, delivery_lng, tracking_events FROM order_info WHERE porter_order_id = ?");
$stmt->execute([$order_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(["error" => "Order not found"]);
    exit;
}

$row['tracking_events'] = $row['tracking_events'] ? json_decode($row['tracking_events'], true) : [];

echo json_encode($row);
