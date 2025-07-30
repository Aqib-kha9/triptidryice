<?php
// Test script for Porter API debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Porter configuration
$porter_mode = 'production';
$porter_api_key = $porter_mode === 'production'
    ? 'e54a5be0-80fb-41de-80bf-89daf6c56766'
    : '659d4aaf-3797-4186-b7c3-2c231f5d0e22';

$porter_api_url = $porter_mode === 'production'
    ? 'https://pfe-apigw.porter.in'
    : 'https://pfe-apigw-uat.porter.in';

// Test payload with valid Mumbai coordinates
$test_payload = [
    "request_id" => "TEST_" . time(),
    "pickup_details" => [
        "address" => [
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
        ]
    ],
    "drop_details" => [
        "address" => [
            "apartment_address" => "Flat 101",
            "street_address1" => "123 Andheri West",
            "street_address2" => "Near Metro Station",
            "landmark" => "Andheri Metro",
            "city" => "Mumbai",
            "state" => "Maharashtra",
            "pincode" => "400058",
            "country" => "India",
            "lat" => 19.1197,
            "lng" => 72.8464,
            "contact_details" => [
                "name" => "Test Customer",
                "phone_number" => "+919876543210"
            ]
        ]
    ],
    "delivery_instructions" => [
        "instructions_list" => [
            [
                "type" => "text",
                "description" => "Dry ice delivery - handle with care. Keep in insulated container."
            ]
        ]
    ]
];

echo "<h2>Porter API Test</h2>";
echo "<h3>Configuration:</h3>";
echo "<ul>";
echo "<li>Mode: $porter_mode</li>";
echo "<li>API URL: $porter_api_url</li>";
echo "<li>API Key: " . substr($porter_api_key, 0, 10) . "...</li>";
echo "</ul>";

echo "<h3>Test Payload:</h3>";
echo "<pre>" . json_encode($test_payload, JSON_PRETTY_PRINT) . "</pre>";

// Make API call
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $porter_api_url . "/v1/orders/create");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "x-api-key: $porter_api_key",
    "Content-Type: application/json",
    "User-Agent: Tripti-DryIce-Test/1.0"
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$curl_error = curl_error($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_info = curl_getinfo($ch);
curl_close($ch);

$porter_response = json_decode($response, true);

echo "<h3>API Response:</h3>";
echo "<ul>";
echo "<li>HTTP Status: $http_status</li>";
echo "<li>Curl Error: " . ($curl_error ?: 'None') . "</li>";
echo "<li>Response Time: " . $curl_info['total_time'] . " seconds</li>";
echo "</ul>";

echo "<h3>Response Body:</h3>";
echo "<pre>" . json_encode($porter_response, JSON_PRETTY_PRINT) . "</pre>";

echo "<h3>Raw Response:</h3>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Test location validation
echo "<h3>Location Validation Test:</h3>";
$test_locations = [
    ['Mumbai - Valid', 19.0760, 72.8777, true],
    ['Delhi - Invalid', 28.7041, 77.1025, false],
    ['Bangalore - Invalid', 12.9716, 77.5946, false],
    ['Mumbai Suburbs - Valid', 19.1197, 72.8464, true]
];

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Location</th><th>Lat</th><th>Lng</th><th>Valid for Mumbai</th></tr>";

foreach ($test_locations as $location) {
    $name = $location[0];
    $lat = $location[1];
    $lng = $location[2];
    $isValid = ($lat >= 18.8 && $lat <= 19.5 && $lng >= 72.7 && $lng <= 73.2);
    $expected = $location[3];
    $status = $isValid === $expected ? '✅ PASS' : '❌ FAIL';
    
    echo "<tr>";
    echo "<td>$name</td>";
    echo "<td>$lat</td>";
    echo "<td>$lng</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}
echo "</table>";

// Test different payload variations
echo "<h3>Payload Variations Test:</h3>";
$variations = [
    'Minimal' => [
        "request_id" => "TEST_MIN_" . time(),
        "pickup_details" => ["address" => $test_payload['pickup_details']['address']],
        "drop_details" => ["address" => $test_payload['drop_details']['address']]
    ],
    'With Instructions' => $test_payload,
    'Without Instructions' => [
        "request_id" => "TEST_NO_INST_" . time(),
        "pickup_details" => ["address" => $test_payload['pickup_details']['address']],
        "drop_details" => ["address" => $test_payload['drop_details']['address']]
    ]
];

foreach ($variations as $name => $payload) {
    echo "<h4>Testing: $name</h4>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $porter_api_url . "/v1/orders/create");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "x-api-key: $porter_api_key",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    $status = in_array($http_status, [200, 201]) ? '✅ SUCCESS' : '❌ FAILED';
    
    echo "<p><strong>$status</strong> - HTTP $http_status</p>";
    if ($http_status !== 200 && $http_status !== 201) {
        echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
    }
}
?> 