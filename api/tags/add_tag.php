<?php

require_once('../../db/db.inc.php');
require_once('../../db/queries/tags.inc.php');
require_once('../../utils/respond.php');


$content = trim(file_get_contents("php://input"));
$apiLogger->info('Add tag request');

$data = json_decode($content, true);

  //If json_decode failed, the JSON is invalid.
if( is_array($data)) {
    try {
        $tag = addTag($pdo_conn, $data, $dbLogger);
        if (!$tag) {
            throw new Exception("no new tag returned", 500);
        }
        
        echo json_encode($tag);
        exit();
    } catch (Exception $e) {
        respondWith($e->getCode(), $e->getMessage());
    }
} else {
    $apiLogger->error('Invalid json payload to add tag request');
    respondWith(500, 'invalid json');
}