<?php
require('../util/Connection.php');
require('../util/SessionCheck.php');
require('Header.php');
require('../util/Logger.php');

$fromid    = $_POST['fromid']    ?? '';
$toid      = $_POST['toid']      ?? '';
$commodity = $_POST['commodity'] ?? '';

if ($fromid === '' || $toid === '' || $commodity === '') {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$query  = "SELECT * FROM optimised_table ORDER BY last_updated DESC LIMIT 1";
$result = mysqli_query($con, $query);
$id     = '';
while ($row = mysqli_fetch_array($result)) {
    $id = $row['id'];
}

$tablename = "optimiseddata_" . $id;

// Check if admin has already approved this row — if so, block reset
$checkQuery = "SELECT approve_admin FROM " . $tablename . "
               WHERE from_id   = '" . mysqli_real_escape_string($con, $fromid)   . "'
                 AND to_id     = '" . mysqli_real_escape_string($con, $toid)      . "'
                 AND commodity = '" . mysqli_real_escape_string($con, $commodity) . "'
               LIMIT 1";
$checkResult = mysqli_query($con, $checkQuery);
$checkRow    = mysqli_fetch_assoc($checkResult);

if ($checkRow && $checkRow['approve_admin'] === 'yes') {
    echo json_encode(['success' => false, 'message' => 'Admin has already approved this row — reset not allowed']);
    exit;
}

// Reset district-side fields only
$resetQuery = "UPDATE " . $tablename . "
               SET approve_district      = '',
                   new_id_district       = '',
                   new_name_district     = '',
                   reason_district       = '',
                   new_distance_district = ''
               WHERE from_id   = '" . mysqli_real_escape_string($con, $fromid)    . "'
                 AND to_id     = '" . mysqli_real_escape_string($con, $toid)       . "'
                 AND commodity = '" . mysqli_real_escape_string($con, $commodity)  . "'";

$ok = mysqli_query($con, $resetQuery);

writeLog("District -> Reset Row | " . $_SESSION['district_user'] . " | " . $fromid . " - " . $toid . " - " . $commodity);

mysqli_close($con);
echo json_encode(['success' => (bool)$ok]);
?>
<?php require('Fullui.php'); ?>
