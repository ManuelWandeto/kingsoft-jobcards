<?php

require_once('../../db/db.inc.php');
require_once('../../db/queries/tags.inc.php');
require_once('../../utils/respond.php');

$content = trim(file_get_contents("php://input"));
$apiLogger->info('Update tag request');
$decoded = json_decode($content, true);

  //If json_decode failed, the JSON is invalid.
if( is_array($decoded)) {
    try {
        $tag = updateTag($pdo_conn, $decoded, $dbLogger);
        if (!$tag) {
            throw new Exception("Error updating tag with id: ". $decoded['id'], 500);
        }
        echo json_encode($tag);
        exit();
    } catch (Exception $e) {
        respondWith($e->getCode(), $e->getMessage());
    }
} else {
    $apiLogger->error('Invalid json payload to update tag request');
    respondWith(500, "Invalid json");
}