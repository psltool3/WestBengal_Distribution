<?php
require('../util/Connection.php');
require('../util/SessionCheck.php');
require('../util/Logger.php');

// Increase script execution time and memory limits for large datasets
ini_set('memory_limit', '1G');
set_time_limit(600); // 10 minutes

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

if (!isset($_POST['month']) || !isset($_POST['year'])) {
    echo json_encode(["status" => "error", "message" => "Month and year are required parameters."]);
    exit;
}

$month = mysqli_real_escape_string($con, $_POST['month']);
$year = mysqli_real_escape_string($con, $_POST['year']);

// 1. Find the latest run ID from the appropriate metadata table for the given month and year
$query = "SELECT id FROM optimised_table WHERE month='$month' AND year='$year' ORDER BY last_updated DESC LIMIT 1";
$result = mysqli_query($con, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    echo json_encode(["status" => "error", "message" => "No optimization data found for month: $month, year: $year."]);
    exit;
}

$row = mysqli_fetch_assoc($result);
$id = $row['id'];
$tablename = "optimiseddata_" . $id;

// 2. Check if the detail table exists
$checkTableQuery = "SHOW TABLES LIKE '$tablename'";
$checkTableResult = mysqli_query($con, $checkTableQuery);

if (!$checkTableResult || mysqli_num_rows($checkTableResult) === 0) {
    echo json_encode(["status" => "error", "message" => "Optimized route details table ($tablename) does not exist."]);
    exit;
}

// 2.5 Check if all tags have been approved by the State Admin
$unapprovedQuery = "SELECT COUNT(*) as cnt FROM `$tablename` WHERE approve_admin IS NULL OR approve_admin = ''";
$unapprovedResult = mysqli_query($con, $unapprovedQuery);
if ($unapprovedResult) {
    $unapprovedRow = mysqli_fetch_assoc($unapprovedResult);
    if ($unapprovedRow['cnt'] > 0) {
        echo json_encode(["status" => "error", "message" => "All tags must be approved by the State Admin before pushing. (" . $unapprovedRow['cnt'] . " rows pending)"]);
        exit;
    }
}

function getMonthNumber($monthStr) {
    if (is_numeric($monthStr)) {
        return (int)$monthStr;
    }
    $monthStr = strtolower(trim($monthStr));
    $months = [
        'jan' => 1, 'january' => 1,
        'feb' => 2, 'february' => 2,
        'mar' => 3, 'march' => 3,
        'apr' => 4, 'april' => 4,
        'may' => 5,
        'jun' => 6, 'june' => 6,
        'jul' => 7, 'july' => 7,
        'aug' => 8, 'august' => 8,
        'sep' => 9, 'sept' => 9, 'september' => 9,
        'oct' => 10, 'october' => 10,
        'nov' => 11, 'november' => 11,
        'dec' => 12, 'december' => 12
    ];
    return isset($months[$monthStr]) ? $months[$monthStr] : 0;
}

// 3. Query all route movement rows from the detail table
$dataQuery = "SELECT * FROM `$tablename`";
$dataResult = mysqli_query($con, $dataQuery);

if (!$dataResult) {
    echo json_encode(["status" => "error", "message" => "Failed to retrieve route optimization details from the database."]);
    exit;
}

$routeData = [];
while ($rowDetail = mysqli_fetch_assoc($dataResult)) {
    // If state/admin changed the warehouse, override from_id with new_id_admin before pushing
    if (!empty($rowDetail['new_id_admin'])) {
        $wh_id = $rowDetail['new_id_admin'];
        $query_warehouse = "SELECT latitude, longitude, district FROM warehouse WHERE id='$wh_id'";
        $result_warehouse = mysqli_query($con, $query_warehouse);
        if ($result_warehouse && mysqli_num_rows($result_warehouse) > 0) {
            $row_warehouse = mysqli_fetch_assoc($result_warehouse);
            $rowDetail["from_lat"] = $row_warehouse['latitude'];
            $rowDetail["from_long"] = $row_warehouse['longitude'];
            $rowDetail["from_district"] = $row_warehouse['district'];
        }
        $rowDetail["from_id"] = $rowDetail['new_id_admin'];
        $rowDetail["from_name"] = $rowDetail['new_name_admin'];
        $rowDetail["distance"] = $rowDetail['new_distance_admin'];
    } elseif (!empty($rowDetail['new_id_district']) && $rowDetail['district_change_approve'] === "yes") {
        // District suggested a warehouse AND state approved it — use district's suggestion
        $wh_id = $rowDetail['new_id_district'];
        $query_warehouse = "SELECT latitude, longitude, district FROM warehouse WHERE id='$wh_id'";
        $result_warehouse = mysqli_query($con, $query_warehouse);
        if ($result_warehouse && mysqli_num_rows($result_warehouse) > 0) {
            $row_warehouse = mysqli_fetch_assoc($result_warehouse);
            $rowDetail["from_lat"] = $row_warehouse['latitude'];
            $rowDetail["from_long"] = $row_warehouse['longitude'];
            $rowDetail["from_district"] = $row_warehouse['district'];
        }
        $rowDetail["from_id"] = $rowDetail['new_id_district'];
        $rowDetail["from_name"] = $rowDetail['new_name_district'];
        $rowDetail["distance"] = $rowDetail['new_distance_district'];
    }

    $comm = isset($rowDetail['commodity']) ? trim((string)$rowDetail['commodity']) : "FRice";
    $fromDist = isset($rowDetail['from_district']) ? trim((string)$rowDetail['from_district']) : "";
    $toDist = isset($rowDetail['to_district']) ? trim((string)$rowDetail['to_district']) : "";

    $from_id = isset($rowDetail['from_id']) ? trim((string)$rowDetail['from_id']) : "";
    $to_id = isset($rowDetail['to_id']) ? trim((string)$rowDetail['to_id']) : "";

    $routeData[] = [
        "commodity" => $comm,
        "distance" => isset($rowDetail['distance']) ? trim((string)$rowDetail['distance']) : "0",
        "from" => isset($rowDetail['from']) ? trim((string)$rowDetail['from']) : "",
        "from_district" => $fromDist,
        "from_id" => $from_id,
        "from_lat" => isset($rowDetail['from_lat']) ? trim((string)$rowDetail['from_lat']) : "0",
        "from_long" => isset($rowDetail['from_long']) ? trim((string)$rowDetail['from_long']) : "0",
        "from_name" => isset($rowDetail['from_name']) ? trim((string)$rowDetail['from_name']) : "",
        "from_state" => isset($rowDetail['from_state']) ? trim((string)$rowDetail['from_state']) : "West Bengal",
        "quantity" => isset($rowDetail['quantity']) ? trim((string)$rowDetail['quantity']) : "0",
        "scenario" => isset($rowDetail['scenario']) ? trim((string)$rowDetail['scenario']) : "",
        "status" => !empty($rowDetail['status']) ? trim((string)$rowDetail['status']) : "Implemented",
        "to" => isset($rowDetail['to']) ? trim((string)$rowDetail['to']) : "",
        "to_district" => $toDist,
        "to_id" => $to_id,
        "to_lat" => isset($rowDetail['to_lat']) ? trim((string)$rowDetail['to_lat']) : "0",
        "to_long" => isset($rowDetail['to_long']) ? trim((string)$rowDetail['to_long']) : "0",
        "to_name" => isset($rowDetail['to_name']) ? trim((string)$rowDetail['to_name']) : "",
        "to_state" => isset($rowDetail['to_state']) ? trim((string)$rowDetail['to_state']) : "West Bengal"
    ];
}

mysqli_free_result($dataResult);
mysqli_close($con);

if (empty($routeData)) {
    echo json_encode(["status" => "error", "message" => "No route data records found in table: $tablename."]);
    exit;
}

$clientId = "0f01870e4296536ec5003bca7ef112405742f7c90de710e555f3d0f28bba33f1";

// 4. Authenticate first to retrieve token
$authUrl = "https://wbfss.wb.gov.in/apigateway/api/AuthenticationUserData";

$chAuth = curl_init();
curl_setopt_array($chAuth, array(
    CURLOPT_URL            => $authUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST  => 'POST',
    CURLOPT_POSTFIELDS     => json_encode(array("clientId" => $clientId)),
    CURLOPT_HTTPHEADER     => array('Content-Type: application/json'),
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
));

$authResponse = curl_exec($chAuth);
$authError    = curl_error($chAuth);
curl_close($chAuth);

if ($authError) {
    echo json_encode(["status" => "error", "message" => "Authentication Connection Error: " . $authError]);
    exit();
}

$authData = json_decode($authResponse, true);
$token = $authData['status']['token'] ?? null;

if (!$token) {
    echo json_encode(["status" => "error", "message" => "Authentication failed. Could not retrieve token."]);
    exit();
}

// 5. Group and send the API payload by district (to handle large datasets)
$monthNum = getMonthNumber($month);
$chunks = [];
foreach ($routeData as $row) {
    $districtKey = !empty($row['to_district']) ? $row['to_district'] : 'Unknown';
    $chunks[$districtKey][] = $row;
}
$totalChunks = count($chunks);

$username = isset($_SESSION['user']) ? $_SESSION['user'] : 'unknown';
writeLog("User -> Push Route Optimization API Started | Month: $month, Year: $year | Total Count: " . count($routeData) . " | Districts (Chunks): $totalChunks | User: $username");

$apiUrl = "https://wbfss.wb.gov.in/apigateway/api/OptimisedData";
$hasError = false;
$errorMsg = "";
$allResponses = [];

foreach ($chunks as $districtName => $districtData) {
    $subChunks = array_chunk($districtData, 500); // 500 records per request
    $totalSubChunks = count($subChunks);

    foreach ($subChunks as $subIndex => $chunk) {
        $payload = [
            "ClientId" => $clientId,
            "month" => (string)$monthNum,
            "total_rows" => (int)count($chunk),
            "year" => (string)$year,
            "data" => $chunk
        ];
        $jsonPayload = json_encode($payload);
        
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            "Authorization: Bearer $token"
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);          

        $apiResponse = curl_exec($ch);
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $logPartLabel = $totalSubChunks > 1 ? " (Part " . ($subIndex + 1) . "/$totalSubChunks)" : "";

        if ($apiResponse === false) {
            $hasError = true;
            $errorMsg .= "District $districtName$logPartLabel cURL Error: $curlError; ";
            writeLog("Error -> Push Route Optimization API Failed | District $districtName$logPartLabel | Month: $month, Year: $year | cURL Error: $curlError");
        } else {
            writeLog("Response -> Push Route Optimization API Response | District $districtName$logPartLabel | Month: $month, Year: $year | HTTP: $httpStatusCode | Response: $apiResponse");
            if ($httpStatusCode >= 200 && $httpStatusCode < 300) {
                $responseList = json_decode($apiResponse, true);
                // Handle response warnings/errors if any returned by gateway
                if (is_array($responseList) && isset($responseList['status']['error']) && $responseList['status']['error'] !== '0') {
                    $hasError = true;
                    $errorMsg .= "District $districtName$logPartLabel API Alert: " . ($responseList['status']['msg'] ?? 'Unknown error') . "; ";
                }
                $allResponses[] = $apiResponse;
            } else {
                $hasError = true;
                $errorMsg .= "District $districtName$logPartLabel failed with HTTP $httpStatusCode: $apiResponse; ";
            }
        }
    }
}

if ($hasError) {
    echo json_encode(["status" => "error", "message" => trim($errorMsg, "; ")]);
} else {
    echo json_encode(["status" => "success", "message" => "All $totalChunks districts pushed successfully. Last response: " . end($allResponses)]);
}
?>
