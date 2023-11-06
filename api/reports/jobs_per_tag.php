<?php
require_once('../../db/db.inc.php');
require_once('../../db/queries/reports.inc.php');
require_once('../../utils/respond.php');
require_once('../../utils/logger.php');

try {
    $report = getJobsPertag($pdo_conn, $_GET, $dbLogger);
    echo json_encode($report);
    exit();
} catch (Exception $e) {
    respondWith($e->getCode(), $e->getMessage());
}
