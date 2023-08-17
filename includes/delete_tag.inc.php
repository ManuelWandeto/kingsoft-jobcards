<?php

include_once('db.inc.php');
include_once('functions.inc.php');
include_once('../utils/respond.php');

$id = $_GET["id"];

try {
    $ok = deleteTag($conn, $id);
    if (!$ok) {
        throw new Exception("failed to delete tag", 500);
    }
    echo $ok;
    exit();
} catch (Exception $e) {
    respondWith($e->getCode(), $e->getMessage());
}