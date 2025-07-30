
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $drop_name = $_POST['drop_name'];
    $drop_phone = $_POST['drop_phone'];
    $drop_address = $_POST['drop_address'];
    $comments = $_POST['comments'];
    $cart_items = json_decode($_POST['cart'], true);

    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += floatval($item['price']);
    }

    $gst = round($subtotal * 0.18, 2);
    $shipping_charge = 30; // Flat rate shipping
    $total = $subtotal + $gst + $shipping_charge;

    $data = [
        "request_id" => uniqid("REQ_"),
        "delivery_instructions" => [
            "instructions_list" => [
                [
                    "type" => "text",
                    "description" => "handle with care"
                ]
            ]
        ],
        "pickup_details" => [
            "address" => [
                "apartment_address" => "",
                "street_address1" => "Tripti Gases Pvt.Ltd / Tripti Cryo Gases Mahape",
                "street_address2" => "Plot No.57 A , M.I.D.C., T.T.C.Industrial Area",
                "landmark" => "Mahape, Navi Mumbai",
                "city" => "Navi Mumbai",
                "state" => "Maharashtra",
                "pincode" => "400710",
                "country" => "India",
                "lat" => 19.1075,
                "lng" => 73.0151,
                "contact_details" => [
                    "name" => "Tripti Dispatch",
                    "phone_number" => "+919999999999"
                ]
            ]
        ],
        "drop_details" => [
            "address" => [
                "apartment_address" => "",
                "street_address1" => $drop_address,
                "city" => "Navi Mumbai",
                "state" => "Maharashtra",
                "pincode" => "400710",
                "country" => "India",
                "lat" => 19.0896,
                "lng" => 73.0000,
                "contact_details" => [
                    "name" => $drop_name,
                    "phone_number" => $drop_phone
                ]
            ]
        ],
        "additional_comments" => $comments . " | Subtotal: ₹$subtotal, GST: ₹$gst, Shipping: ₹$shipping_charge, Total: ₹$total"
    ];

    $ch = curl_init('https://pfe-apigw-uat.porter.in/v1/orders/create');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: 659d4aaf-3797-4186-b7c3-2c231f5d0e22'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    curl_close($ch);

    echo "<pre>Response from Porter API:\n$response</pre>";
} else {
    echo "Invalid request.";
}
?>
