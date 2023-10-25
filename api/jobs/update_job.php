<?php
require_once('../../db/db.inc.php');
require_once('../../db/queries/jobs.inc.php');
require_once('../../utils/respond.php');
require_once('../../utils/convert.php');
require_once('../../utils/logger.php');

$apiLogger->info('Update job request');
if (isset($_SERVER['CONTENT_LENGTH']) 
    && (int) $_SERVER['CONTENT_LENGTH'] > convertToBytes(ini_get('post_max_size'))) 
{
    respondWith(400, 'File too large');
}
try {
    $job = updateJob($pdo_conn, $_POST, $dbLogger);
    if (!$job) {
        throw new Exception("Error updating job with id: ". $_POST['id'], 500);
    }
    echo json_encode($job);
    exit();
} catch (Exception $e) {
    respondWith($e->getCode(), $e->getMessage());
}