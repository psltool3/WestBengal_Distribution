<?php
// Disable timeouts (can run for several minutes)
@set_time_limit(0);
@ini_set('max_execution_time', '0');

require('../util/Connection.php');
require('../structures/FPS.php');
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

// 3. Retrieve FPS Data
$fpsUrl = "https://wbfss.wb.gov.in/apigateway/api/RouteOptimization/GetFps";

$chData = curl_init();
curl_setopt_array($chData, array(
    CURLOPT_URL            => $fpsUrl,
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
    echo "Error connecting to FPS API: " . $error . "\n";
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

// Clear existing FPS data
mysqli_query($con, "TRUNCATE TABLE fps");

$insertedCount = 0;
$errorCount    = 0;

foreach ($dataList as $data) {
    try {
        if (empty($data['id']) || empty($data['name']) || empty($data['district'])) {
            $errorCount++;
            continue;
        }

        $fps = new FPS;
        $fps->setDistrict(formatName($data['district']));
        $fps->setName($data['name']); // Preserves original characters
        $fps->setId($data['id']);
        $fps->setType($data['type'] ?? 'Normal FPS');

        $lat = isset($data['latitude']) && is_numeric($data['latitude']) ? $data['latitude'] : 0;
        $lon = isset($data['longitude']) && is_numeric($data['longitude']) ? $data['longitude'] : 0;
        $fps->setLatitude($lat);
        $fps->setLongitude($lon);

        $demandWheat = 0;
        $demandRice  = 0;
        $demandFrice = 0;
        $demandAtta  = 0;

        if (isset($data['demands']) && is_array($data['demands'])) {
            foreach ($data['demands'] as $d) {
                $commodity = $d['commodity'] ?? '';
                $val       = $d['demand'] ?? 0;
                if (stripos($commodity, 'Atta') !== false) {
                    $demandAtta = $val;
                } elseif (stripos($commodity, 'Wheat') !== false) {
                    $demandWheat = $val;
                } elseif (stripos($commodity, 'Frice') !== false) {
                    $demandFrice = $val;
                } elseif (stripos($commodity, 'Rice') !== false) {
                    $demandRice = $val;
                }
            }
        }

        $fps->setDemand($demandAtta);
        $fps->setDemandwheat($demandWheat);
        $fps->setDemandrice($demandRice);
        $fps->setDemandfrice($demandFrice);
        $fps->setUniqueid(substr(uniqid("FPS_"), 0, 15));
        $fps->setActive($data['active'] ?? '1');

        $insertQuery = $fps->insert($fps);
        if (mysqli_query($con, $insertQuery)) {
            $insertedCount++;
            writeLog("User -> " . ($_SESSION['user'] ?? 'SYSTEM') .
                     " | FPS loaded from API -> " . ($data['name'] ?? ''));
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
echo "Source: GetFps\n";

echo "<script type='text/javascript'>";
echo "setTimeout(function() {";
echo "window.location.href = '../FPS.php';";
echo "}, 3000);";
echo "</script>";

require('Fullui.php');
?>
