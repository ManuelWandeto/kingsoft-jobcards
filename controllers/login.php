<?php 

require_once('../utils/redirect.php');
require_once('../db/db.inc.php');
require_once('../db/functions.inc.php');

if(!isset($_POST["submit"])) {
    echo "Unauthorised route";
    redirect('../Login/custom-login.php');
}

$username = $_POST['username'];
$pwd = $_POST['password'];


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


// check user folder for files that are cleared from db and delete them
$userdir = UPLOAD_PATH . 'user_' . $_SESSION['user_id'] . DIRECTORY_SEPARATOR;
if (file_exists($userdir)) {
    $userfiles = array_diff(scandir($userdir), array('.', '..'));
    foreach ($userfiles as $file) {
        $query = "SELECT * FROM `jc_attachments` WHERE `file_name` = ? AND `uploaded_by` = ?;";
        $attachment = queryRow($conn, "find-file-attachment", $query, 'si', $file, $_SESSION["user_id"]);
        if(!$attachment) {
            unlink($userdir . $file);
        }
    }
}
redirect('../index.php');