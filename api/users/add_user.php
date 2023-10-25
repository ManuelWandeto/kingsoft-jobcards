<?php
require_once('../../db/db.inc.php');
require_once('../../db/queries/users.inc.php');
require_once('../../utils/respond.php');

$content = trim(file_get_contents("php://input"));

$decoded = json_decode($content, true);
$apiLogger->info('New user sign-up request');
  //If json_decode failed, the JSON is invalid.
if( is_array($decoded)) {
    try {
        $user = signUpUser($pdo_conn, $decoded, $dbLogger);
        if (!$user) {
            throw new Exception("no new user returned", 500);
        }
        echo json_encode($user);
        exit();
    } catch (Exception $e) {
        respondWith($e->getCode(), $e->getMessage());
    }
} else {
    $apiLogger->error('Invalid json payload to sign up user request');
    respondWith(500, 'Invalid json');
}

