<?php
session_start();
define('UPLOAD_PATH', '../uploads/');
$upload_dir = UPLOAD_PATH . 'user_'. $_SESSION['user_id'] . '/';

$filename = $_GET['name'];
$filepath = $upload_dir . $filename;
$size = $_GET['size'];
$type = $_GET['type'];
// echo "Name: $filename \r\n";
// echo "Size: $size \r\n";
// echo "Mime type: $type \r\n";
// exit;

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
}
?>