<?php

include_once('db.inc.php');
include_once('functions.inc.php');
include_once('../utils/respond.php');

$content = trim(file_get_contents("php://input"));

$decoded = json_decode($content, true);

  //If json_decode failed, the JSON is invalid.
if( is_array($decoded)) {
    try {
        $job = addJob($conn, $decoded);
        if (!$job) {
            throw new Exception("no new job returned", 500);
        }
        
        echo json_encode($job);
        exit();
    } catch (Exception $e) {
        respondWith($e->getCode(), $e->getMessage());
    }
} else {
    respondWith(500, 'Invalid json');
}