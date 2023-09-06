<?php

require_once('../../db/db.inc.php');
require_once('../../db/queries/clients.inc.php');
require_once('../../utils/respond.php');

try {
    $client = addClient($pdo_conn, $_POST);
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