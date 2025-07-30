<?php
// db.php

$host = "localhost";
$dbname = "tripti_dryice";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "error" => "DB connection failed",
        "details" => $e->getMessage()
    ]);
    exit;
}
