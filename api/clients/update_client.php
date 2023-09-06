<?php

require_once('../../db/db.inc.php');
require_once('../../db/queries/clients.inc.php');
require_once('../../utils/respond.php');

try {
    $client = updateClient($pdo_conn, $_POST);
    if (!$client) {
        throw new Exception("Error updating client with id: ". $_POST['id'], 500);
    }
    echo json_encode($client);
    exit();
} catch (Exception $e) {
    respondWith($e->getCode(), $e->getMessage());
}