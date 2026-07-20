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

$query  = "SELECT * FROM optimised_table_leg1 ORDER BY last_updated DESC LIMIT 1";
$result = mysqli_query($con, $query);
$id     = '';
while ($row = mysqli_fetch_array($result)) {
    $id = $row['id'];
}

$tablename = "optimiseddata_leg1_" . $id;

// Reset admin-side fields only
$resetQuery = "UPDATE " . $tablename . "
               SET approve_admin      = '',
                   new_id_admin       = '',
                   new_name_admin     = '',
                   reason_admin       = '',
                   new_distance_admin = '',
                   district_change_approve = ''
               WHERE from_id   = '" . mysqli_real_escape_string($con, $fromid)    . "'
                 AND to_id     = '" . mysqli_real_escape_string($con, $toid)       . "'
                 AND commodity = '" . mysqli_real_escape_string($con, $commodity)  . "'";

$ok = mysqli_query($con, $resetQuery);

writeLog("Admin -> Reset Row Leg1 | " . $_SESSION['user'] . " | " . $fromid . " - " . $toid . " - " . $commodity);

mysqli_close($con);
echo json_encode(['success' => (bool)$ok]);
?>
<?php require('Fullui.php'); ?>
