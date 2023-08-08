<?php
include_once('../utils/respond.php');

session_start();
if(!isset($_SESSION["user_id"]) || !isset($_SESSION["role"])) {
    respondWith(403, "User not logged in");
}
$sessionVars = [
    "user_id" => $_SESSION['user_id'],
    "username" => $_SESSION['username'],
    "email" => $_SESSION['email'],
    "role" => $_SESSION['role']
];
echo json_encode($sessionVars);
exit();