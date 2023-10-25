<?php

require_once('../../db/db.inc.php');
require_once('../../db/queries/files.inc.php');
require_once('../../utils/respond.php');
require_once('../../utils/logger.php');

$content = trim(file_get_contents("php://input"));

$decoded = json_decode($content, true);

$apiLogger->info('Delete attachment request');
  //If json_decode failed, the JSON is invalid.
if( is_array($decoded)) {
    try {
        $ok = deleteAttachedFile($pdo_conn, $decoded, $dbLogger);
        if (!$ok) {
            throw new Exception("Failed to delete file: ". $decoded['filename'], 500);
        }
        echo json_encode($ok);
        exit();
    } catch (Exception $e) {
        respondWith($e->getCode(), $e->getMessage());
    }
} else {
    $apiLogger->error('invalid json payload to delete attachment request');
    respondWith(500, "Invalid json");
}