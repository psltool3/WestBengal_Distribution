<?php

require('../util/Connection.php');
require('../util/SessionFunction.php');
require('../util/Logger.php'); 

if(!SessionCheck()){
	return;
}

require('Header.php');

$id = $_POST["uid"];

$query = "SELECT * FROM depot WHERE uniqueid='$id'";
$result = mysqli_query($con,$query);
$numrows = mysqli_num_rows($result);

if($numrows>0){
	$row = mysqli_fetch_assoc($result);
	$status = $row['active'];
	$depotname = $row['name'];
	if($status==0){
		writeLog("District User ->" ." Depot Active -> ". $_SESSION['district_user'] . "| " . $depotname);
		$query = "UPDATE depot SET active='1' WHERE uniqueid='$id'";
		mysqli_query($con,$query);
	}
	else{
		writeLog("District User ->" ." Depot InActive -> ". $_SESSION['district_user'] . "| " . $depotname);
		$query = "UPDATE depot SET active='0' WHERE uniqueid='$id'";
		mysqli_query($con,$query);
	}
}


mysqli_close($con);

echo "<script>window.location.href = '../Depot.php';</script>";

?>
<?php require('Fullui.php');  ?>