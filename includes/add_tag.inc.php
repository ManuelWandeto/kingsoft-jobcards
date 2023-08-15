<?php

include_once('db.inc.php');
include_once('functions.inc.php');
include_once('../utils/respond.php');


$content = trim(file_get_contents("php://input"));

$data = json_decode($content, true);

  //If json_decode failed, the JSON is invalid.
if( is_array($data)) {
    try {
        $tag = addTag($conn, $data);
        if (!$tag) {
            throw new Exception("no new tag returned", 500);
        }
        
        echo json_encode($tag);
        exit();
    } catch (Exception $e) {
        respondWith($e->getCode(), $e->getMessage());
    }
} else {
    respondWith(500, 'invalid json');
}