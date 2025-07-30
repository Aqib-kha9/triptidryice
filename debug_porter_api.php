<?php
// Comprehensive Porter API Debug Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Porter API Debug Analysis</h1>";

// Test different API configurations
$configurations = [
    'UAT' => [
        'url' => 'https://pfe-apigw-uat.porter.in',
        'key' => '659d4aaf-3797-4186-b7c3-2c231f5d0e22'
    ],
    'Production' => [
        'url' => 'https://pfe-apigw.porter.in',
        'key' => 'e54a5be0-80fb-41de-80bf-89daf6c56766'
    ]
];

// Test different payload structures
$payload_variations = [
    'Basic' => [
        "request_id" => "DEBUG_" . time(),
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
        ]
    ],
    'With Instructions' => [
        "request_id" => "DEBUG_INST_" . time(),
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
                    "description" => "Dry ice delivery - handle with care."
                ]
            ]
        ]
    ],
    'Minimal Required' => [
        "request_id" => "DEBUG_MIN_" . time(),
        "pickup_details" => [
            "address" => [
                "street_address1" => "Tripti Dry Ice Co.",
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
                "street_address1" => "123 Andheri West",
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
        ]
    ]
];

// Test API connectivity first
echo "<h2>🔗 API Connectivity Test</h2>";
foreach ($configurations as $env => $config) {
    echo "<h3>Testing $env Environment</h3>";
    
    // Test basic connectivity
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    echo "<p><strong>$env:</strong> HTTP $http_status - " . ($curl_error ? "Error: $curl_error" : "Connected") . "</p>";
}

// Test different payload structures
echo "<h2>📦 Payload Structure Tests</h2>";
foreach ($configurations as $env => $config) {
    echo "<h3>Testing $env Environment</h3>";
    
    foreach ($payload_variations as $payload_name => $payload) {
        echo "<h4>Testing: $payload_name</h4>";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config['url'] . "/v1/orders/create");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "x-api-key: " . $config['key'],
            "Content-Type: application/json",
            "User-Agent: Tripti-DryIce-Debug/1.0"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        $curl_info = curl_getinfo($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        $status_color = in_array($http_status, [200, 201]) ? 'green' : 'red';
        echo "<p style='color: $status_color;'><strong>$payload_name:</strong> HTTP $http_status</p>";
        
        if ($http_status !== 200 && $http_status !== 201) {
            echo "<details>";
            echo "<summary>Error Details</summary>";
            echo "<p><strong>Response:</strong></p>";
            echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
            echo "<p><strong>Raw Response:</strong></p>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
            echo "<p><strong>Curl Error:</strong> " . ($curl_error ?: 'None') . "</p>";
            echo "<p><strong>Request Time:</strong> " . $curl_info['total_time'] . " seconds</p>";
            echo "</details>";
        }
    }
}

// Test API key validation
echo "<h2>🔑 API Key Validation</h2>";
foreach ($configurations as $env => $config) {
    echo "<h3>Testing $env API Key</h3>";
    
    // Test with invalid API key
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config['url'] . "/v1/orders/create");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload_variations['Basic']));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "x-api-key: INVALID_KEY_FOR_TESTING",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    echo "<p><strong>Invalid Key Test:</strong> HTTP $http_status</p>";
    if ($http_status === 401 || $http_status === 403) {
        echo "<p style='color: green;'>✅ API key validation is working (rejected invalid key)</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Unexpected response for invalid key</p>";
        echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
    }
}

// Test different endpoints
echo "<h2>🌐 Endpoint Tests</h2>";
foreach ($configurations as $env => $config) {
    echo "<h3>Testing $env Endpoints</h3>";
    
    $endpoints = [
        '/v1/orders/create' => 'POST',
        '/v1/orders' => 'GET',
        '/health' => 'GET',
        '/status' => 'GET'
    ];
    
    foreach ($endpoints as $endpoint => $method) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config['url'] . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload_variations['Basic']));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "x-api-key: " . $config['key'],
                "Content-Type: application/json"
            ]);
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "x-api-key: " . $config['key']
            ]);
        }
        
        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $status_color = in_array($http_status, [200, 201]) ? 'green' : 'red';
        echo "<p style='color: $status_color;'><strong>$endpoint ($method):</strong> HTTP $http_status</p>";
    }
}

// Test with different content types
echo "<h2>📋 Content Type Tests</h2>";
foreach ($configurations as $env => $config) {
    echo "<h3>Testing $env Content Types</h3>";
    
    $content_types = [
        'application/json' => json_encode($payload_variations['Basic']),
        'application/x-www-form-urlencoded' => http_build_query($payload_variations['Basic'])
    ];
    
    foreach ($content_types as $content_type => $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config['url'] . "/v1/orders/create");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "x-api-key: " . $config['key'],
            "Content-Type: $content_type"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);
        echo "<p><strong>$content_type:</strong> HTTP $http_status</p>";
        if ($http_status !== 200 && $http_status !== 201) {
            echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
        }
    }
}

echo "<h2>📊 Summary</h2>";
echo "<p>This debug script has tested:</p>";
echo "<ul>";
echo "<li>API connectivity to both UAT and Production environments</li>";
echo "<li>Different payload structures (Basic, With Instructions, Minimal Required)</li>";
echo "<li>API key validation</li>";
echo "<li>Different endpoints</li>";
echo "<li>Different content types</li>";
echo "</ul>";

echo "<h3>🔍 Next Steps:</h3>";
echo "<ol>";
echo "<li>Check if the Porter API keys are still valid</li>";
echo "<li>Verify if the API endpoints have changed</li>";
echo "<li>Contact Porter support for API documentation updates</li>";
echo "<li>Consider using a different delivery partner API</li>";
echo "</ol>";
?> 