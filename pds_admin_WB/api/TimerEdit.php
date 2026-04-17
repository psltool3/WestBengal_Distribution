<?php

require('../util/Connection.php');
require('../util/SessionFunction.php');

if(!SessionCheck()){
	return;
}

require('Header.php');

$date = $_POST['date'];
$time = $_POST['time'];

$checkQuery = "SELECT * FROM timer LIMIT 1";
$checkResult = mysqli_query($con, $checkQuery);
if (mysqli_num_rows($checkResult) > 0) {
	$query = "UPDATE timer SET deadline_date='$date', deadline_time='$time' WHERE 1";
	mysqli_query($con,$query);
} else {
	$query = "INSERT INTO timer (deadline_date, deadline_time) VALUES ('$date', '$time')";
	mysqli_query($con,$query);
}
mysqli_close($con);

echo "<script>window.location.href = '../Timer.php';</script>";

?>
<?php require('Fullui.php');  ?>