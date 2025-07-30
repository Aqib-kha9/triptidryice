<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");

include "db.php"; // this gives us $pdo

if (!isset($_GET['order_id'])) {
    echo json_encode(["error" => "Missing order_id"]);
    exit;
}

$order_id = $_GET['order_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM order_info WHERE order_id = :order_id");
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        echo json_encode(["error" => "Order not found"]);
    } else {
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($order);
    }
} catch (PDOException $e) {
    echo json_encode(["error" => "Query failed", "details" => $e->getMessage()]);
}
?>
