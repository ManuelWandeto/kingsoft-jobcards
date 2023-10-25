<?php

require_once('../../db/db.inc.php');
require_once('../../db/queries/tags.inc.php');
require_once('../../utils/respond.php');

$id = $_GET["id"];
$apiLogger->info('Delete tag request');
try {
    $ok = deleteTag($pdo_conn, $id, $dbLogger);
    if (!$ok) {
        throw new Exception("failed to delete tag", 500);
    }
    echo json_encode($ok);
    exit();
} catch (Exception $e) {
    respondWith($e->getCode(), $e->getMessage());
}