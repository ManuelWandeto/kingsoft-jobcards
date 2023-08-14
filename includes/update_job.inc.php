<?php

include_once('db.inc.php');
include_once('functions.inc.php');
include_once('../utils/respond.php');
include_once('../utils/convert.php');

if (isset($_SERVER['CONTENT_LENGTH']) 
    && (int) $_SERVER['CONTENT_LENGTH'] > convertToBytes(ini_get('post_max_size'))) 
{
    respondWith(400, 'File too large');
}

try {
    $job = updateJob($conn, $_POST);
    if (!$job) {
        throw new Exception("Error updating job with id: ". $_POST['id'], 500);
    }
    echo json_encode($job);
    exit();
} catch (Exception $e) {
    respondWith($e->getCode(), $e->getMessage());
}