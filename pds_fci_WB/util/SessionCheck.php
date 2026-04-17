<?php
// Set session ini settings BEFORE starting session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

session_start();

// -----------------------------
// Session Timeout
// -----------------------------
$timeout_duration = 12000;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: AdminLogin.html?error=session_timeout");
    exit();
}
$_SESSION['last_activity'] = time();

// -----------------------------
// Auth Check
// -----------------------------
require('Connection.php');
if (isset($_SESSION['user']) && isset($_SESSION['token'])) {
    $user = $_SESSION['user'];
    $token = $_SESSION['token'];
    $query = "SELECT * FROM login WHERE username='$user' AND token='$token'";
    $result = mysqli_query($con, $query);

    if (mysqli_num_rows($result) === 0) {
        session_unset();
        session_destroy();
        header("Location: AdminLogin.html?error=invalid_token");
        exit();
    }

    $currentLoginTime = date("Y-m-d H:i:s");
    $queryUpdate = "UPDATE login SET lastlogin='$currentLoginTime' WHERE username='$user'";
    mysqli_query($con, $queryUpdate);
} else {
    header("Location: AdminLogin.html?error=no_session");
    exit();
}
?>
