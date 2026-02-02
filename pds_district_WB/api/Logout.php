<?php

session_start();
session_regenerate_id(true);
$_SESSION = [];
session_destroy();
$_SESSION['district_name'] = null;
$_SESSION['district_user'] = null;
header("Location:../Login.html");

?>