<?php
require_once('../../db/db.inc.php');
require_once('../../db/queries/users.inc.php');
require_once('../../utils/respond.php');

$id = $_GET["id"];
$apiLogger->info('Delete user request');
try {
    $ok = deleteUser($pdo_conn, $id, $dbLogger);
    if (!$ok) {
        throw new Exception("delete failed", 500);
    }
    echo json_encode($ok);
    exit();
} catch (Exception $e) {
    respondWith($e->getCode(), $e->getMessage());
}