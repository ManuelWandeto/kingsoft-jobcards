<?php
require_once('../../db/db.inc.php');
require_once('../../db/queries/users.inc.php');
require_once('../../utils/respond.php');

$id = $_GET["id"];

try {
    $ok = deleteUser($conn, $id);
    if (!$ok) {
        throw new Exception("delete failed", 500);
    }
    echo $ok;
    exit();
} catch (Exception $e) {
    respondWith($e->getCode(), $e->getMessage());
}