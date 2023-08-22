<?php

require_once('../../db/db.inc.php');
require_once('../../db/queries/clients.inc.php');
require_once('../../utils/respond.php');

$content = trim(file_get_contents("php://input"));

$decoded = json_decode($content, true);

  //If json_decode failed, the JSON is invalid.
if( is_array($decoded)) {
    try {
        $client = updateClient($conn, $decoded);
        if (!$client) {
            throw new Exception("Error updating client with id: ". $decoded['id'], 500);
        }
        echo json_encode($client);
        exit();
    } catch (Exception $e) {
        respondWith($e->getCode(), $e->getMessage());
    }
} else {
    respondWith(500, "Invalid json");
}