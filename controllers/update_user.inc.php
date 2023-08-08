<?php

include_once('../includes/db.inc.php');
include_once('../includes/functions.inc.php');
include_once('../utils/redirect.php');
// session_start();

if(!isset($_POST["submit"]) || !isset($_SESSION['user_id'])) {
    redirect('../Login/brandlogin.php');
}

$id = $_SESSION["user_id"];
$username = $_POST["username"];
$email = $_POST["email"];
$phone = $_POST["phone"];
$location = $_POST["currentLocation"];
$task = $_POST["currentTask"];

$nameExists = uidExists($conn, $username, $username);
if($nameExists && $nameExists["id"] != $id) {
    redirect('../index.php?page=profile&error=username+exists');
}
$emailExists = uidExists($conn, $email, $email);
if($emailExists && $emailExists["id"] != $id) {
    redirect('../index.php?page=profile&error=email+exists');
}


$userdata = [
    "id" => $id,
    "username" => $username,
    "email" => $email,
    "phone" => $phone,
    "current_location" => $location,
    "current_task" => $task,
];
try {
    $updatedUser = updateUser($conn, $userdata);
    if(!$updatedUser) {
        throw new Exception('No updated user returned');
    }
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['phone'] = $phone;
    $_SESSION['task'] = $task;
    $_SESSION['location'] = $location;
    redirect('../index.php?page=profile&success');
} catch (Exception $e) {
    redirect('../index.php?page=profile&error='.str_replace(" ", "+", $e->getMessage()));
}
