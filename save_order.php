<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ----------- DB CONFIG -----------
require_once "db.php";

// ----------- INPUT & LOGGING -----------
$data = json_decode(file_get_contents("php://input"), true);
file_put_contents("logs/input_log.txt", json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL, FILE_APPEND);

if (!$data || !isset($data['payment_id'], $data['customer'], $data['items'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit;
}

// ----------- BASIC VARIABLES -----------
$order_id = uniqid("order_");
$created_at = date('Y-m-d H:i:s');

// ----------- SAVE TO `orders` TABLE -----------
try {
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
        ":payment_id" => $data['payment_id'],
        ":name" => $data['customer']['name'],
        ":phone" => $data['customer']['phone'],
        ":email" => $data['customer']['email'],
        ":address" => $data['customer']['address'],
        ":pin" => $data['customer']['pinCode'],
        ":subtotal" => $data['subtotal'],
        ":gst" => $data['gst'],
        ":shipping" => $data['shipping'],
        ":total" => $data['total'],
        ":created_at" => $created_at
    ]);

    // Save order items to order_items table
    foreach ($data['items'] as $item) {
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

} catch (PDOException $e) {
    file_put_contents("logs/db_error_log.txt", "[" . date("Y-m-d H:i:s") . "] DB Error: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => 'DB insert failed', 'details' => $e->getMessage()]);
    exit;
}

// ----------- PORTER CONFIG -----------
$porter_mode = 'production'; // Using production environment (UAT is broken)

$porter_api_key = $porter_mode === 'production'
    ? 'e54a5be0-80fb-41de-80bf-89daf6c56766'
    : '659d4aaf-3797-4186-b7c3-2c231f5d0e22';

$porter_api_url = $porter_mode === 'production'
    ? 'https://pfe-apigw.porter.in'
    : 'https://pfe-apigw-uat.porter.in';

// ----------- ADDRESS & LOCATION -----------
$pickup_details = [
    "apartment_address" => "Warehouse 3",
    "street_address1" => "Tripti Dry Ice Co.",
    "street_address2" => "Kurla Industrial Estate",
    "landmark" => "Near XYZ Landmark",
    "city" => "Mumbai",
    "state" => "Maharashtra",
    "pincode" => "400070",
    "country" => "India",
    "lat" => 19.0760,
    "lng" => 72.8777,
    "contact_details" => [
        "name" => "Tripti Warehouse",
        "phone_number" => "+919999999999"
    ]
];

// Validate and clean location data
$lat = floatval($data['customer']['lat'] ?? 0);
$lng = floatval($data['customer']['lng'] ?? 0);
$pinCode = $data['customer']['pinCode'] ?? '';

// Validate coordinates are within Mumbai area
$isValidMumbaiLocation = ($lat >= 18.8 && $lat <= 19.5 && $lng >= 72.7 && $lng <= 73.2);

if (!$isValidMumbaiLocation) {
    // If coordinates are not in Mumbai, use default Mumbai coordinates
    $lat = 19.0760;
    $lng = 72.8777;
    file_put_contents("logs/location_error_log.txt", "[" . date("Y-m-d H:i:s") . "] Invalid coordinates for Mumbai: lat=$lat, lng=$lng. Using default Mumbai coordinates.\n", FILE_APPEND);
}

$drop_details = [
    "apartment_address" => $data['customer']['apartment'] ?? 'N/A',
    "street_address1" => $data['customer']['address'] ?? '',
    "street_address2" => '',
    "landmark" => $data['customer']['landmark'] ?? 'N/A',
    "city" => "Mumbai",
    "state" => "Maharashtra",
    "pincode" => $pinCode,
    "country" => "India",
    "lat" => $lat,
    "lng" => $lng,
    "contact_details" => [
        "name" => $data['customer']['name'],
        "phone_number" => "+91" . $data['customer']['phone']
    ]
];

// ----------- PORTER PAYLOAD -----------
$porter_payload = [
    "request_id" => "TRIPTI_" . time(),
    "pickup_details" => [ "address" => $pickup_details ],
    "drop_details" => [ "address" => $drop_details ],
    "delivery_instructions" => [
        "instructions_list" => [
            [
                "type" => "text",
                "description" => "Dry ice delivery - handle with care. Keep in insulated container."
            ]
        ]
    ]
];

file_put_contents("logs/porter_payload.txt", json_encode($porter_payload, JSON_PRETTY_PRINT) . PHP_EOL, FILE_APPEND);

// ----------- CURL REQUEST TO PORTER -----------
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $porter_api_url . "/v1/orders/create");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($porter_payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "x-api-key: $porter_api_key",
    "Content-Type: application/json",
    "User-Agent: Tripti-DryIce/1.0"
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$curl_error = curl_error($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$porter_response = json_decode($response, true);

// ----------- LOG PORTER RESPONSE -----------
file_put_contents("logs/porter_response.txt", json_encode([
    "timestamp" => date("Y-m-d H:i:s"),
    "http_status" => $http_status,
    "response" => $porter_response,
    "raw" => $response,
    "curl_error" => $curl_error,
    "request_payload" => $porter_payload
], JSON_PRETTY_PRINT) . PHP_EOL, FILE_APPEND);

// Log detailed error information
if ($http_status !== 200 && $http_status !== 201) {
    file_put_contents("logs/porter_error_detailed.txt", "[" . date("Y-m-d H:i:s") . "] Porter API Error:\n" . 
        "HTTP Status: $http_status\n" .
        "Curl Error: $curl_error\n" .
        "Response: " . json_encode($porter_response, JSON_PRETTY_PRINT) . "\n" .
        "Request Payload: " . json_encode($porter_payload, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
}

// ----------- UPDATE ORDERS TABLE WITH PORTER INFO -----------
try {
    $porter_order_id = $porter_response['order_id'] ?? null;
    $tracking_url = $porter_response['tracking_url'] ?? null;
    $porter_status = $porter_response['status'] ?? 'Booked';

    $stmt_update = $pdo->prepare("
        UPDATE orders 
        SET porter_order_id = :porter_order_id,
            porter_tracking_url = :tracking_url
        WHERE order_id = :order_id
    ");

    $stmt_update->execute([
        ":porter_order_id" => $porter_order_id,
        ":tracking_url" => $tracking_url,
        ":order_id" => $order_id
    ]);

} catch (PDOException $e) {
    file_put_contents("logs/db_error_log.txt", "[" . date("Y-m-d H:i:s") . "] Update Error: " . $e->getMessage() . "\n", FILE_APPEND);
}

// ----------- FINAL RESPONSE -----------
if (in_array($http_status, [200, 201]) && isset($porter_response['order_id'])) {
    // Success response
    $success_response = [
        "success" => true,
        "porter_success" => true,
        "message" => "Order saved and Porter booking confirmed!",
        "order_id" => $order_id,
        "porter_order_id" => $porter_response['order_id'],
        "tracking_url" => $porter_response['tracking_url'] ?? null,
        "estimated_pickup_time" => $porter_response['estimated_pickup_time'] ?? null,
        "estimated_delivery_time" => $porter_response['estimated_delivery_time'] ?? null,
        "delivery_charge" => $porter_response['estimated_fare_details']['minor_amount'] ?? null,
        "currency" => $porter_response['estimated_fare_details']['currency'] ?? 'INR'
    ];

    file_put_contents("logs/success_log.txt", "[" . date("Y-m-d H:i:s") . "] Success: " . json_encode($success_response, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
    echo json_encode($success_response);

} else {
    // Error response with fallback
    $error_response = [
        "success" => true,
        "porter_success" => false,
        "message" => "Order saved successfully! Porter booking failed, but your order is confirmed. We'll contact you shortly for delivery details.",
        "order_id" => $order_id,
        "porter_error" => $porter_response,
        "http_status" => $http_status,
        "curl_error" => $curl_error,
        "debug_info" => [
            "api_url" => $porter_api_url,
            "mode" => $porter_mode,
            "request_id" => $porter_payload['request_id'],
            "location_validation" => $isValidMumbaiLocation ?? 'unknown'
        ],
        "fallback_message" => "Your order has been saved and payment processed successfully. Our team will contact you within 30 minutes to arrange delivery."
    ];

    file_put_contents("logs/porter_error_log.txt", "[" . date("Y-m-d H:i:s") . "] Porter Error:\n" . json_encode($error_response, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
    echo json_encode($error_response);
}
?>
