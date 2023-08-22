<?php
require_once('../../utils/constants.php');
function uploadFiles() {
    if (!isset($_FILES['attachments']['name'])) {
        throw new Exception("attachments is not set", 500);
    }
    $files = array_filter($_FILES['attachments']['name']);
    $paths = [];
    $total = count($files);
    if(!$total) {
        return false;
    }
    for ($i=0; $i < $total; $i++) { 
        if($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
            $upload_dir = UPLOAD_PATH . 'user_'. $_SESSION['user_id'] . '/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $filename = $_FILES['attachments']['name'][$i];
            $tempPath = $_FILES['attachments']['tmp_name'][$i];
            $filepath = $upload_dir . basename($_FILES['attachments']['name'][$i]);
            $acceptedTypes = array('doc', 'docx', 'pdf', 'csv', 'xlsx');
            $fileType = strtolower(pathinfo($_FILES['attachments']['name'][$i], PATHINFO_EXTENSION));
            // check if file already exists
            if (file_exists($filepath)) {
                unlink($filepath);
            };
            // check if file is not of an accepted type
            if (!in_array($fileType, $acceptedTypes) && strpos($_FILES['attachments']['type'][$i], 'image/') !== 0) {
                throw new Exception("$filename is not of an accepted type, only documents and images", 400);
            }
            if ($tempPath) {
                if(move_uploaded_file($tempPath, $filepath)) {
                    $paths[] = $filepath;
                } else {
                    return false;
                }
            } else {
                throw new Exception("error reading temp path of file", 500);
            }
        } else {
            $file = $_FILES['attachments']['name'][$i];

            switch ($_FILES['attachments']['error'][$i]) {
                case UPLOAD_ERR_INI_SIZE:
                    throw new Exception("$file exceeds max allowed size", 400);

                case UPLOAD_ERR_PARTIAL:
                    throw new Exception("$file was only partially uploaded", 500);

                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new Exception("$file missing a temporary folder", 500);

                case UPLOAD_ERR_NO_FILE:
                    throw new Exception("$file was not uploaded", 500);
                    
                case UPLOAD_ERR_CANT_WRITE:
                    throw new Exception("Failed to write $file to disk", 500);
            
                default:
                    throw new Exception("Uncaught upload error", 500);
            }
        }
    }
    return $paths;
}
function deleteUploadedFile(mysqli $conn, string $filepath) {
    $filename = basename($filepath);
    
    if (!file_exists($filepath)) {
        throw new Exception("$filename doesn't exist", 404);
    }
    if (!strpos(dirname($filepath), 'user_'. $_SESSION['user_id'])) {
        throw new Exception("Unauthorised deletion, you can only delete files you posted", 401);
    }
    deleteAttachment($conn, $filepath);
    unlink($filepath);
    return true;
}
function deleteAttachment(mysqli $conn, string $filepath) {
    $sql = "DELETE FROM jc_attachments WHERE `file_path` = ?;";
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt, $sql)) {
        throw new Exception("Error preparing delete attachment query: ".mysqli_stmt_error($stmt), 500);
    }
    if (!mysqli_stmt_bind_param($stmt, 's', $filepath)) {
        throw new Exception("Error binding params to delete attachment query: ".mysqli_stmt_error($stmt), 400);
    }
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing delete attachment query: ".mysqli_stmt_error($stmt), 500);
    }
    mysqli_stmt_close($stmt);
}
function getFileInfo(string $filepath) {
    $filename = basename($filepath);
    if (!file_exists($filepath)) {
        throw new Exception("Error getting file info: $filename doesn't exist", 404);
    }
    $filesize = filesize($filepath);
    $filetype = mime_content_type($filepath);
    $lastModified = filemtime($filepath);
    return [
        "name" => $filename,
        "size" => $filesize,
        "type" => $filetype,
        "db_path" => $filepath,
        "lastModified" => $lastModified
    ];
}