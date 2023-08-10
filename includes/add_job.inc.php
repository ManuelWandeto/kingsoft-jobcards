<?php

include_once('db.inc.php');
include_once('functions.inc.php');
include_once('../utils/respond.php');

function convertToBytes($value) {
    $limit = trim(strtolower($value), 'mkg');
    $last = strtolower($value[strlen($value)-1]);
  
    switch($last) {
      case 'g':
        $limit *= 1000000000;
        break;
      case 'm':
        $limit *= 1000000;
        break;
      case 'k':
        $limit *= 1000;
        break;
    }
    return $limit;
  }

if (isset($_SERVER['CONTENT_LENGTH']) 
    && (int) $_SERVER['CONTENT_LENGTH'] > convertToBytes(ini_get('post_max_size'))) 
{
    respondWith(400, 'File too large');
}

try {
    $job = addJob($conn, $_POST);
    if (!$job) {
        throw new Exception("no new job returned", 500);
    }
    
    echo json_encode($job);
    exit();
} catch (Exception $e) {
    respondWith($e->getCode(), $e->getMessage());
}