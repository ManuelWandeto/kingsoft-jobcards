<?php

require_once('../../db/db.inc.php');
require_once('../../db/queries/tags.inc.php');
require_once('../../utils/respond.php');

try {
    $tags = getTags($pdo_conn, $dbLogger);
    echo json_encode($tags);
    exit();
} catch (Exception $e) {
    respondWith($e->getCode(), $e->getMessage());
}
