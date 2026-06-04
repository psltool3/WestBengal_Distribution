<?php
// Disable timeouts (can run for several minutes)
@set_time_limit(0);
@ini_set('max_execution_time', '0');

require('../util/Connection.php');
require('../structures/Mill.php');
require('../util/SessionFunction.php');
require('../util/SessionCheck.php');
require('../util/Logger.php');
require('../util/Security.php');
require('Header.php');

function formatName($name) {
    if (!$name) return '';
    $name = preg_replace('/[^a-zA-Z0-9_ ]/', '', $name);
    $name = ucwords(strtolower($name));
    return trim($name);
}

// 1. Authenticate to retrieve token
$clientId = "0f01870e4296536ec5003bca7ef112405742f7c90de710e555f3d0f28bba33f1";
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
    echo "Authentication Connection Error: " . $authError . "\n";
    exit();
}

$authData = json_decode($authResponse, true);
$token = $authData['status']['token'] ?? null;

if (!$token) {
    echo "Authentication failed. Could not retrieve token.\n";
    exit();
}

// 2. Encryption setups
$publicKey = "-----BEGIN PUBLIC KEY-----\n" .
"MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtJ3hMv+p3fdr64HSnUku\n" .
"Z1fv44xYYtHAu115I//AOBNAwc8NX1JrNWR9UE+mBFlUZjwgb8sOWo82frfw+iLs\n" .
"1zyj47OzPJNVajmzro82iRxsPXhc635t9sLOQloqMVi1pQspR0Rh21zlKPNmDV2r\n" .
"5WVTj21187srJKV43aUqUargTxu15rTqgSLGM3S/1MR3GCh5i4B88wm/p52jrAYJ\n" .
"RPLhknzC3Vv0Epo1Ie9QBSiWul6C9YRfLRUtCtq+JIT+DrQD85IlVG4kAzrUbWS4\n" .
"p/eqQ97/GGipeVtf+0Yt8b8FHEhdYyMMSJFXDtGhDPWV/z7m7sTqQzxaxivC9C6j\n" .
"mQIDAQAB\n" .
"-----END PUBLIC KEY-----";

function encrypt($text, $pubKey) {
    $encrypted = '';
    if (openssl_public_encrypt($text, $encrypted, $pubKey, OPENSSL_PKCS1_PADDING)) {
        return base64_encode($encrypted);
    }
    return '';
}

// Automatically request last month and year
$rawMonth = date('m', strtotime('first day of last month'));
$rawYear  = date('Y', strtotime('first day of last month'));

$encMonth = encrypt($rawMonth, $publicKey);
$encYear  = encrypt($rawYear, $publicKey);

$apiData = array(
    "ClientId" => $clientId,
    "Month"    => $encMonth,
    "Year"     => $encYear
);

// 3. Retrieve RiceMill Data
$millUrl = "https://wbfss.wb.gov.in/apigateway/api/RouteOptimization/getRiceMill";

$chData = curl_init();
curl_setopt_array($chData, array(
    CURLOPT_URL            => $millUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST  => 'POST',
    CURLOPT_POSTFIELDS     => json_encode($apiData),
    CURLOPT_HTTPHEADER     => array(
        'Content-Type: application/json',
        "Authorization: Bearer $token"
    ),
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
));

$response = curl_exec($chData);
$httpCode = curl_getinfo($chData, CURLINFO_HTTP_CODE);
$error    = curl_error($chData);
curl_close($chData);

if ($error) {
    echo "Error connecting to RiceMill API: " . $error . "\n";
    exit();
}
if ($httpCode !== 200) {
    echo "API returned HTTP error code: " . $httpCode . "\n";
    exit();
}

$apiResponse = json_decode($response, true);
if (!$apiResponse || ($apiResponse['status']['error'] ?? '') !== '0') {
    $msg = $apiResponse['status']['msg'] ?? 'Unknown API error';
    echo "API returned error: " . $msg . "\n";
    exit();
}

$dataList = $apiResponse['dataResult']['dataList'] ?? [];

// Clear existing Mill (dcp table) data
mysqli_query($con, "TRUNCATE TABLE dcp");

$insertedCount = 0;
$errorCount    = 0;

foreach ($dataList as $data) {
    try {
        if (empty($data['id']) || empty($data['name']) || empty($data['district'])) {
            $errorCount++;
            continue;
        }

        $dcp = new DCP;
        $dcp->setDistrict(formatName($data['district']));
        $dcp->setName($data['name']);
        $dcp->setId($data['id']);
        $dcp->setType($data['type'] ?? 'Raw');

        $lat = isset($data['latitude']) && is_numeric($data['latitude']) ? $data['latitude'] : 0;
        $lon = isset($data['longitude']) && is_numeric($data['longitude']) ? $data['longitude'] : 0;
        $dcp->setLatitude($lat);
        $dcp->setLongitude($lon);

        $dcp->setDemand($data['capacity'] ?? 0); // Capacity of Mill (Qtl)
        $dcp->setDemandrice($data['proc_capacity'] ?? 0); // Processing Capacity (Qtl)

        $dcp->setUniqueid(substr(uniqid("MIL_"), 0, 15));
        $dcp->setActive($data['active'] ?? '1');

        $insertQuery = $dcp->insert($dcp);
        if (mysqli_query($con, $insertQuery)) {
            $insertedCount++;
            writeLog("User -> " . ($_SESSION['user'] ?? 'SYSTEM') .
                     " | Mill loaded from API -> " . ($data['name'] ?? ''));
        } else {
            $errorCount++;
        }

    } catch (Exception $e) {
        $errorCount++;
        continue;
    }
}

mysqli_close($con);

echo "Data Load Complete\n";
echo "-------------------------\n";
echo "New records inserted : $insertedCount\n";
echo "Records with errors  : $errorCount\n";
echo "-------------------------\n";
echo "Source: getRiceMill\n";

echo "<script type='text/javascript'>";
echo "setTimeout(function() {";
echo "window.location.href = '../Mill.php';";
echo "}, 3000);";
echo "</script>";

require('Fullui.php');
?>
