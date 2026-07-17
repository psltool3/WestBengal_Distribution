<?php
// Disable timeouts (can run for several minutes)
@set_time_limit(0);
@ini_set('max_execution_time', '0');

require('../util/Connection.php');
require('../structures/Depot.php');
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
$clientId = "967198dc9799348b162db106b890f8a0afc2eed83d2a8cb6238d112dad670188";
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
"MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEArqBHYkXkh8xliiMTYvpo\n" .
"X107nolZ6lmW45TkfE+PJIrBLkF1rJlvLgb9dx3TOEl6EvsyS1N6uSbbxK4dw6iQ\n" .
"oFIUfKY3aU1CMcoqFEqtgkfTnRmBCFtt3RrcNV5SP+OIMBZgRf8/QS8it/KmQKOO\n" .
"SQZMbgRdZ/pmKss9BC9G2MyozOwMn1/lg0OYdIcFBnFMH5vViUYfWcpacVPWg7jV\n" .
"/oTs4FQ3wA+LAzajHf40ZdRm8p5/DyJV6Y5uVTU6KMSycyihGqcnrwLiuMY7Z8/Z\n" .
"W/E54xnej/FR/7ojfQ3j7J+Cll7vVEoWpD2FOGwQqqylAs3NoFBYXnPiYzNk2sqN\n" .
"jQIDAQAB\n" .
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

// 3. Retrieve Godown Data
$godownUrl = "https://wbfss.wb.gov.in/apigateway/api/RouteOptimization/getGodown";

$chData = curl_init();
curl_setopt_array($chData, array(
    CURLOPT_URL            => $godownUrl,
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
    echo "Error connecting to Godown API: " . $error . "\n";
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

// Clear existing Depot data
mysqli_query($con, "TRUNCATE TABLE depot");

$insertedCount = 0;
$errorCount    = 0;

foreach ($dataList as $data) {
    try {
        if (empty($data['id']) || empty($data['name']) || empty($data['district'])) {
            $errorCount++;
            continue;
        }

        $depot = new Depot;
        $depot->setDistrict(formatName($data['district']));
        $depot->setName($data['name']);
        $depot->setId($data['id']);
        $depot->setType($data['type'] ?? 'DCFS');

        $lat = isset($data['latitude']) && is_numeric($data['latitude']) ? $data['latitude'] : 0;
        $lon = isset($data['longitude']) && is_numeric($data['longitude']) ? $data['longitude'] : 0;
        $depot->setLatitude($lat);
        $depot->setLongitude($lon);

        $demandRice  = 0;
        $demandPrice = 0;

        if (isset($data['inventories']) && is_array($data['inventories'])) {
            foreach ($data['inventories'] as $inv) {
                $commodity = $inv['commodity'] ?? '';
                $val       = $inv['inventory'] ?? 0;
                if (stripos($commodity, 'Raw Rice') !== false || stripos($commodity, 'Raw_Rice') !== false) {
                    $demandRice = $val;
                } elseif (stripos($commodity, 'Parboiled Rice') !== false || stripos($commodity, 'Parboiled_Rice') !== false) {
                    $demandPrice = $val;
                } elseif (stripos($commodity, 'Rice') !== false) {
                    $demandRice = $val;
                }
            }
        }

        $depot->setDemand($demandRice);
        $depot->setDemandrice($demandPrice);
        $depot->setUniqueid(substr(uniqid("DEP_"), 0, 15));
        $depot->setActive($data['active'] ?? '1');

        $insertQuery = $depot->insert($depot);
        if (mysqli_query($con, $insertQuery)) {
            $insertedCount++;
            writeLog("User -> " . ($_SESSION['user'] ?? 'SYSTEM') .
                     " | Depot loaded from API -> " . ($data['name'] ?? ''));
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
echo "Source: getGodown\n";

echo "<script type='text/javascript'>";
echo "setTimeout(function() {";
echo "window.location.href = '../Depot.php';";
echo "}, 3000);";
echo "</script>";

require('Fullui.php');
?>
