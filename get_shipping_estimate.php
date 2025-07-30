<?php
header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);
$pickup = $input["pickupPincode"] ?? "400070";
$drop = $input["pinCode"] ?? "";

if (!$pickup || !$drop) {
    echo json_encode(["success" => false, "message" => "Missing pickup or drop pincode"]);
    exit;
}

// All valid serviceable pincodes
$validPinCodes = [
  "400001", "400002", "400003", "400004", "400005", "400006", "400007", "400008", "400009",
  "400010", "400011", "400012", "400013", "400014", "400015", "400016", "400017", "400018",
  "400019", "400020", "400021", "400022", "400023", "400024", "400025", "400026", "400027",
  "400028", "400029", "400030", "400031", "400032", "400033", "400034", "400035", "400036",
  "400037", "400038", "400039", "400041", "400042", "400043", "400049", "400050", "400051",
  "400052", "400054", "400055", "400056", "400057", "400058", "400059", "400060", "400061",
  "400062", "400063", "400064", "400065", "400066", "400067", "400068", "400069", "400070",
  "400071", "400072", "400073", "400074", "400075", "400076", "400077", "400078", "400079",
  "400080", "400081", "400082", "400083", "400084", "400085", "400086", "400087", "400088",
  "400089", "400090", "400091", "400092", "400093", "400094", "400095", "400096", "400097",
  "400098", "400099", "401074", "401101", "401104", "401105", "401106", "401107", "401201",
  "401202", "401203", "401207", "401208", "401209", "401210", "401303", "401305", "410106",
  "410206", "410208", "410209", "410210", "410211", "410218", "410221", "421005", "421301",
  "421306", "421308", "421309", "421311", "421501", "421502", "421503", "421504", "421505",
  "421506", "400046", "400609", "400611", "400612", "400613", "400614", "401205", "401301",
  "401302", "421001", "421002", "421003", "421004", "421101", "421102", "421103", "421201",
  "421202", "421203", "421204", "421302", "421304", "421305", "421604", "421605"
];
if (!in_array($pickup, $validPinCodes) || !in_array($drop, $validPinCodes)) {
    echo json_encode(["success" => false, "message" => "One or both pincodes are not serviceable"]);
    exit;
}

// Outer zone prefixes for surcharge
$outerZones = ["410", "4216", "4011", "4012", "4215"];
function startsWith($pin, $prefixes) {
    foreach ($prefixes as $prefix) {
        if (strpos($pin, $prefix) === 0) return true;
    }
    return false;
}

// Estimate logic (mocked similar to Porter base)
function estimateBaseFare($pickup, $drop) {
    // If first 3 digits same, assume close → base ₹40
    if (substr($pickup, 0, 3) === substr($drop, 0, 3)) {
        return 40;
    }

    // If first 2 digits same → base ₹60
    if (substr($pickup, 0, 2) === substr($drop, 0, 2)) {
        return 60;
    }

    // Else, long distance → base ₹80
    return 80;
}

$baseFare = estimateBaseFare($pickup, $drop);
$surcharge = 0;
if (startsWith($pickup, $outerZones) || startsWith($drop, $outerZones)) {
    $surcharge = 30;
}

$buffer = 10;
$totalCost = $baseFare + $surcharge + $buffer;

echo json_encode([
    "success" => true,
    "pickup" => $pickup,
    "drop" => $drop,
    "baseFare" => $baseFare,
    "surcharge" => $surcharge,
    "buffer" => $buffer,
    "totalCost" => $totalCost
]);
