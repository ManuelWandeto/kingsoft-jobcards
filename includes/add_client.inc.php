<?php

include_once('db.inc.php');
include_once('functions.inc.php');
include_once('../utils/respond.php');

$content = trim(file_get_contents("php://input"));

$decoded = json_decode($content, true);

  //If json_decode failed, the JSON is invalid.
if( is_array($decoded)) {
    try {
        $client = addClient($conn, $decoded);
        if (!$client) {
            throw new Exception("no new client returned", 500);
        }
        $clientJson = json_encode($client);
        if(!$clientJson) {
            throw new Exception("error encoding new client to json", 500);
        }
        echo $clientJson;
    } catch (Exception $e) {
        respondWith($e->getCode(), $e->getMessage());
    }
} else {
    respondWith(500, 'invalid json');
}