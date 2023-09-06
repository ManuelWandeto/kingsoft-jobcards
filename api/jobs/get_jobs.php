<?php
require_once('../../db/db.inc.php');
require_once('../../db/queries/jobs.inc.php');
require_once('../../utils/respond.php');

// if(!isset($_SESSION["user_id"])) {
//     session_start();
// }

try {
    $jobs = getJobs($pdo_conn, $_GET);
    echo json_encode($jobs);
    exit();
} catch (Exception $e) {
    respondWith($e->getCode(), $e->getMessage());
}
