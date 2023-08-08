<?php

include_once('functions.inc.php');
include_once('db.inc.php');
include_once('../utils/respond.php');

try {
    $clients = getClients($conn);
    echo json_encode($clients);
    exit();
} catch (Exception $e) {
    respondWith($e->getCode(), $e->getMessage());
}
