<?php

include_once('functions.inc.php');
include_once('db.inc.php');
include_once('../utils/respond.php');

try {
    $jobs = getJobs($conn);
    echo json_encode($jobs);
    exit();
} catch (Exception $e) {
    respondWith($e->getCode(), $e->getMessage());
}
