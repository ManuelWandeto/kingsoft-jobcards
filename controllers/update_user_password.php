<?php

require_once('../db/db.inc.php');
require_once('../db/queries/utils.inc.php');
require_once('../utils/redirect.php');
// session_start();

if(!isset($_POST["submit"]) || !isset($_SESSION['user_id'])) {
    redirect('../Login/brandlogin.php');
}

$id = $_SESSION['user_id'];
$oldPassword = $_POST["old-password"];
$newPassword = $_POST["new-password"];
$repeatPwd = $_POST["repeat-password"];
$user = IdExistsPdo($pdo_conn, $id, 'jc_users');

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
    $stmt = $pdo_conn->prepare($sql);
    $stmt->execute([$newHashedPassword, $id]);
    redirect('../index.php?page=profile&success');
} catch (Exception $e) {
    redirect('../index.php?page=profile&error='.str_replace(" ", "+", $e->getMessage()));
}
