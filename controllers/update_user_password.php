<?php

include_once('../includes/db.inc.php');
include_once('../includes/functions.inc.php');
include_once('../utils/redirect.php');
// session_start();

if(!isset($_POST["submit"]) || !isset($_SESSION['user_id'])) {
    redirect('../Login/brandlogin.php');
}

$id = $_SESSION['user_id'];
$oldPassword = $_POST["old-password"];
$newPassword = $_POST["new-password"];
$repeatPwd = $_POST["repeat-password"];
$user = IdExists($conn, $id, 'jc_users');

$hashedPassword = $user["password"];
if (!password_verify($oldPassword, $hashedPassword)) {
    redirect('../index.php?page=profile&error=incorrect+current+password');
}
if($newPassword == $oldPassword) {
    redirect('../index.php?page=profile&error=new+password+same+as+old');
}
if ($newPassword !== $repeatPwd) {
    redirect('../index.php?page=profile&error=passwords+mismatch');
}
$newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
try {
    $sql = 'UPDATE jc_users SET `password` = ? WHERE id = ?;';
    $stmt = mysqli_stmt_init($conn);
    if(!mysqli_stmt_prepare($stmt, $sql)) {
        throw new Exception("Error preparing sql statement");
    }
    if (!mysqli_stmt_bind_param($stmt, 'si', $newHashedPassword, $id)) {
        throw new Exception("Invalid params: ". mysqli_stmt_error($stmt));
    }
    if(!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing update password query");
    }
    mysqli_stmt_close($stmt);
    redirect('../index.php?page=profile&success');
} catch (Exception $e) {
    redirect('../index.php?page=profile&error='.str_replace(" ", "+", $e->getMessage()));
}
