<?php

require_once('../../db/db.inc.php');
require_once('../../db/queries/clients.inc.php');
require_once('../../utils/respond.php');
require_once('../../utils/logger.php');

$id = $_GET["id"];
$apiLogger->info('Delete client request');
try {
    $ok = deleteClient($pdo_conn, $id, $dbLogger);
    if (!$ok) {
        throw new Exception("delete failed", 500);
    }
    echo json_encode($ok);
    exit();
} catch (Exception $e) {
    respondWith($e->getCode(), $e->getMessage());
}