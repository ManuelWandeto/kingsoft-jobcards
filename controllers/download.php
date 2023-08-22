<?php
session_start();
require_once('../utils/constants.php');
$upload_dir = UPLOAD_PATH . 'user_'. $_SESSION['user_id'] . DIRECTORY_SEPARATOR;

$filename = $_GET['name'];
$filepath = $upload_dir . $filename;
$size = $_GET['size'];
$type = $_GET['type'];

if (file_exists($filepath)) {
    header('Content-Description: File Transfer');
    header("Content-Type: $type");
    header("Content-Disposition: attachment; filename=$filename");
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . $size);
    flush();
    readfile($filepath);
    exit;
} else {
    echo 'file not found: '. $filename;
    exit();
}
?>