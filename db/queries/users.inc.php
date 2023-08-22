<?php
require_once(__DIR__ . '/../functions.inc.php');
function signUpUser(mysqli $conn, array $user) {
    if (!isAuthorised(3)) {
        throw new Exception("You are not authorised for this operation!", 401);
    }
    if(uidExists($conn, $user["username"], $user["username"])) {
        throw new Exception("This user already exists!", 400);
    }
    $sql = 'INSERT INTO jc_users (username, email, role, password) VALUES (?, ?, ?, ?);';
    $stmt = mysqli_stmt_init($conn);
    if(!mysqli_stmt_prepare($stmt, $sql)) {
        throw new Exception("Error preparing sql statement: ".mysqli_stmt_error($stmt), 500);
    }
    $hashedPassword = password_hash($user["password"], PASSWORD_DEFAULT);
    mysqli_stmt_bind_param($stmt, 'ssss', $user["username"], $user["email"], $user["role"], $hashedPassword);
    if(!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing add user query: ".mysqli_stmt_error($stmt), 500);
    }
    mysqli_stmt_close($stmt);
    $lastInsertId = mysqli_insert_id($conn);
    $lastInsertRow = mysqli_query($conn, "SELECT * FROM jc_users WHERE id = $lastInsertId");
    if (!$lastInsertRow) {
        throw new Exception("Error getting last insert row", 500);
    }
    $newUser = mysqli_fetch_assoc($lastInsertRow);
    if (!$newUser) {
        throw new Exception("Error getting associative array from last insert row", 500);
    }
    return $newUser;
}
function deleteUser(mysqli $conn, int $userId) {
    if (!isAuthorised(3)) {
        throw new Exception("You are not authorised for this operation!", 401);
    }
    // check if row with given id exists
    if (!IdExists($conn, $userId, 'jc_users')) {
        throw new Exception("User record with id: $userId not found", 404);
    }
    // if id exists, perform deletion
    $sql = 'DELETE FROM jc_users WHERE id = ?;';
    $stmt = mysqli_stmt_init($conn);
    if(!mysqli_stmt_prepare($stmt, $sql)) {
        throw new Exception("Error preparing sql statement: ". mysqli_stmt_error($stmt), 500);
    }
    if (!mysqli_stmt_bind_param($stmt, 'i', $userId)) {
        throw new Exception("Invalid params: ". mysqli_stmt_error($stmt), 400);
    }
    if(!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing delete user query:". mysqli_stmt_error($stmt), 500);
    }
    mysqli_stmt_close($stmt);
    return true;
}
function getUsers(mysqli $conn) {
    $results = mysqli_query($conn, "SELECT * FROM jc_users;");
    if (!$results) {
        throw new Exception("Error getting users");
    }
    $users = array();
    if (mysqli_num_rows($results) === 0) {
        return $users;
    }
    while ($row = mysqli_fetch_assoc($results)) {
        $users[] = $row;
    }
    return $users;
}

function updateUser(mysqli $conn, array $user) {
    $id = $user['id'];
    // check if row with given id exists
    if (!IdExists($conn, $id, 'jc_users')) {
        throw new Exception("User record with id: $id not found", 404);
    }
    // if id exists, perform update
    $sql = 'UPDATE jc_users SET username = ?, email = ?, phone = ?, `current_location` = ?, `current_task` = ? WHERE id = ?;';
    $stmt = mysqli_stmt_init($conn);
    if(!mysqli_stmt_prepare($stmt, $sql)) {
        throw new Exception("Error preparing sql statement", 500);
    }
    if (!mysqli_stmt_bind_param(
        $stmt, 
        'sssssi', 
        $user['username'], 
        $user['email'], 
        $user['phone'], 
        $user['current_location'], 
        $user['current_task'], 
        $id
    )) {
        throw new Exception("Invalid params: ". mysqli_stmt_error($stmt), 400);
    }
    if(!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing insert client query", 500);
    }
    mysqli_stmt_close($stmt);
    return $user;
}
function updateUserRole(mysqli $conn, int $id, string $role) {
    if(!isAuthorised(3)) {
        throw new Exception("You are not authorised for this operation!", 401);
    }
    // check if row with given id exists
    if (!IdExists($conn, $id, 'jc_users')) {
        throw new Exception("User record with id: $id not found", 404);
    }
    // if id exists, perform update
    $sql = 'UPDATE jc_users SET `role` = ? WHERE id = ?;';
    $stmt = mysqli_stmt_init($conn);
    if(!mysqli_stmt_prepare($stmt, $sql)) {
        throw new Exception("Error preparing sql statement", 500);
    }
    if (!mysqli_stmt_bind_param($stmt, 'si', $role, $id)) {
        throw new Exception("Invalid params: ". mysqli_stmt_error($stmt), 400);
    }
    if(!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing insert client query", 500);
    }
    mysqli_stmt_close($stmt);
    return true;
}