<?php 

include_once('../utils/redirect.php');
include_once('../includes/db.inc.php');

if(!isset($_POST["submit"])) {
    echo "Unauthorised route";
    redirect('../Login/custom-login.php');
}

$username = $_POST['username'];
$pwd = $_POST['password'];

include_once('../includes/functions.inc.php');

$user = uidExists($conn, $username, $username);

if (!$user) {
    redirect('../Login/custom-login.php?error=user+not+found');
}
$hashedPassword = $user['password'];

$verify = password_verify($pwd, $hashedPassword);

if (!$verify) {
    redirect('../Login/custom-login.php?error=invalid+password');
}

session_start();
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];
$_SESSION['phone'] = $user['phone'];
$_SESSION['task'] = $user['current_task'];
$_SESSION['location'] = $user['current_location'];
$_SESSION['role'] = $user['role'];

redirect('../index.php');