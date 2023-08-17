<?php

include_once('functions.inc.php');
include_once('db.inc.php');
include_once('../utils/respond.php');

try {
    $tags = getTags($conn);
    echo json_encode($tags);
    exit();
} catch (Exception $e) {
    respondWith($e->getCode(), $e->getMessage());
}
