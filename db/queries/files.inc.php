<?php
use Monolog\Logger;
require_once('../../utils/constants.php');
require_once('../../vendor/autoload.php');
require_once(__DIR__ . '/../functions.inc.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
function handleUploadError($file, $error) {
    switch ($error) {
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

function uploadFile(
    string $inputName, 
    string $upload_dir, 
    string $filenamePrefix = ''
) {
    if (!isset($_FILES[$inputName]['name'])) {
        throw new Exception("$inputName is not set", 500);
    }
    if(!$_FILES[$inputName]['name']) {
        return null;
    }
    if($_FILES[$inputName]['error'] !== UPLOAD_ERR_OK ) {
        $file = $_FILES[$inputName]['name'];
        handleUploadError($file, $_FILES[$inputName]['error']);
    }
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $filename = $filenamePrefix . $_FILES[$inputName]['name'];
    $tempPath = $_FILES[$inputName]['tmp_name'];
    $filepath = $upload_dir . $filename;
    if (file_exists($filepath)) {
        unlink($filepath);
    };
    if ($tempPath) {
        if(move_uploaded_file($tempPath, $filepath)) {
           return basename($filepath);
        } else {
            throw new Exception("error moving file to destination", 500);
        }
    } else {
        throw new Exception("error reading temp path of file", 500);
    }

}
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
    if(!isset($_SESSION['user_id'])) {
        throw new Exception("Cannot create or locate upload dir as user id is not set in session", 400);
    }
    $userdir = 'user_'. $_SESSION['user_id'] . '/';
    $upload_dir = UPLOAD_PATH . $userdir;

    for ($i=0; $i < $total; $i++) { 
        if($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
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
                    $paths[] = basename($filepath);
                } else {
                    throw new Exception("error moving file to destination", 500);
                }
            } else {
                throw new Exception("error reading temp path of file", 500);
            }
        } else {
            $file = $_FILES['attachments']['name'][$i];

            handleUploadError($file, $_FILES['attachments']['error'][$i]);
        }
    }
    return $paths;
}
function extract_user_id($path) {
    $pattern = '/user_(\d+)/';
    preg_match($pattern, $path, $matches);
    return $matches[1];
}
function deleteAttachedFile(PDO $conn, array $attachment, Logger $logger) {
    $filename = $attachment['filename'];
    $filepath = UPLOAD_PATH . "user_{$attachment['uploadedBy']}" . DIRECTORY_SEPARATOR .  $filename;
    
    if (!file_exists($filepath)) {
        throw new Exception("$filename doesn't exist", 404);
    }
    if ($attachment['uploadedBy'] != $_SESSION['user_id']) {
        throw new Exception("Unauthorised deletion, you can only delete files you posted", 401);
    }
    try {
        $stmt = $conn->prepare("DELETE FROM `jc_attachments` WHERE `jobcard_id` = ? AND `file_name` = ? AND `uploaded_by` = ?;");
        $stmt->execute([
            $attachment['jobId'],
            $filename,
            $attachment['uploadedBy']
        ]);
        unlink($filepath);
        return true;
    } catch (PDOException $e) {
        $logger->error('Error removing attachment record', ['message' => $e->getMessage()]);
        throw new Error('Error removing attachment record: '.$e->getMessage());
    }
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
        "uploadedBy" => extract_user_id($filepath),
        "lastModified" => $lastModified
    ];
}