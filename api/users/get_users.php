<?php

require_once('../../db/db.inc.php');
require_once('../../db/queries/users.inc.php');
require_once('../../utils/respond.php');

try {
    $users = getUsers($conn);
    if (!count($users)) {
        throw new Exception("No users was returned", 500);
    }
    echo json_encode($users);
    exit();
} catch (Exception $e) {
    respondWith($e->getCode(), $e->getMessage());
}
