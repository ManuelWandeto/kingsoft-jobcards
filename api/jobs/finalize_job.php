<?php

require_once('../../db/db.inc.php');
require_once('../../db/queries/jobs.inc.php');
require_once('../../utils/respond.php');

$content = trim(file_get_contents("php://input"));

$decoded = json_decode($content, true);

  //If json_decode failed, the JSON is invalid.
if( is_array($decoded)) {
    try {
        $job = finaliseJob($conn, $decoded);
        if (!$job) {
            throw new Exception("Error updating job with id: ". $decoded['id'], 500);
        }
        echo json_encode($job);
        exit();
    } catch (Exception $e) {
        respondWith($e->getCode(), $e->getMessage());
    }
} else {
    respondWith(500, "Invalid json");
}