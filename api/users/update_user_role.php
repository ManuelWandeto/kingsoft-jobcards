<?php
require_once('../../db/db.inc.php');
require_once('../../db/queries/users.inc.php');
require_once('../../utils/respond.php');
require_once('../../utils/logger.php');

$content = trim(file_get_contents("php://input"));
$apiLogger->info('Update user role request');
$decoded = json_decode($content, true);

  //If json_decode failed, the JSON is invalid.
if( is_array($decoded)) {
    try {
        $ok = updateUserRole($pdo_conn, $decoded["id"], $decoded["role"], $dbLogger);
        if (!$ok) {
            throw new Exception("Error updating user's role", 500);
        }
        echo json_encode($ok);
        exit();
    } catch (Exception $e) {
        respondWith($e->getCode(), $e->getMessage());
    }
} else {
    $apiLogger->error('Invalid json payload to update user role request');
    respondWith(500, "Invalid json received");
}