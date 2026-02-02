<?php

session_start();
session_regenerate_id(true);
$_SESSION = [];

// Destroy the session
session_destroy();
$_SESSION['name'] = null;
$_SESSION['user'] = null;
header("Location:../AdminLogin.html");

?>