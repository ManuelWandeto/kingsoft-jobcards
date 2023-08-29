<?php

require_once('../../db/db.inc.php');
require_once('../../db/queries/files.inc.php');
require_once('../../utils/respond.php');

$content = trim(file_get_contents("php://input"));

$decoded = json_decode($content, true);

  //If json_decode failed, the JSON is invalid.
if( is_array($decoded)) {
    try {
        $ok = deleteAttachedFile($conn, $decoded);
        if (!$ok) {
            throw new Exception("Failed to delete file: ". basename($decoded['filepath']), 500);
        }
        echo json_encode($ok);
        exit();
    } catch (Exception $e) {
        respondWith($e->getCode(), $e->getMessage());
    }
} else {
    respondWith(500, "Invalid json");
}