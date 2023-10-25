<?php
use Monolog\Logger;
require_once(__DIR__ . '/../functions.inc.php');
function signUpUser(PDO $conn, array $user, Logger $logger) {
    if (!isAuthorised(3)) {
        throw new Exception("You are not authorised for this operation!", 401);
    }
    try {
        //code...
        if(uidExistsPdo($conn, $user["username"], $user["email"])) {
            throw new Exception("This user already exists!", 400);
        }
        $sql = 'INSERT INTO jc_users (username, email, role, password) VALUES (?, ?, ?, ?);';
        $stmt = $conn->prepare($sql);
        $hashedPassword = password_hash($user["password"], PASSWORD_DEFAULT);
        $stmt->execute([$user["username"], $user["email"], $user["role"], $hashedPassword]);
        $lastInsertId = $conn->lastInsertId();
        $newUser = $conn->query("SELECT * FROM jc_users WHERE id = $lastInsertId")->fetch(PDO::FETCH_ASSOC);
        if (!$newUser) {
            throw new Exception("Error getting new user", 500);
        }
        return $newUser;
    } catch (Exception $e) {
        $logger->error('Error signing up new user', ['message'=>$e->getMessage()]);
        throw new Exception('Error signing up new user: '.$e->getMessage(), 500);
    }
}
function deleteUser(PDO $conn, int $userId, Logger $logger) {
    if (!isAuthorised(3)) {
        throw new Exception("You are not authorised for this operation!", 401);
    }
    try {
        // check if row with given id exists
        if (!IdExistsPdo($conn, $userId, 'jc_users')) {
            throw new Exception("User record with id: $userId not found", 404);
        }
        // if id exists, perform deletion
        $sql = 'DELETE FROM jc_users WHERE id = ?;';
        $stmt = $conn->prepare($sql);
        $stmt->execute([$userId]);
        return true;
    } catch (Exception $e) {
        $logger->error('Error deleting user', ['message'->$e->getMessage()]);
        throw new Exception('Error deleting user: '. $e->getMessage(), 500);
    }
}
function getUsers(PDO $conn, Logger $logger) {
    try {
        $results = $conn->query("SELECT * FROM jc_users;")->fetchAll(PDO::FETCH_ASSOC);
        if (!$results) {
            throw new Exception("Error getting users");
        }
        return $results;
    } catch (Exception $e) {
        $logger->critical('Error getting users', ['message'=>$e->getMessage()]);
        throw new Exception('Error getting users: '.$e->getMessage(), 500);
    }
}

function updateUser(PDO $conn, array $user, Logger $logger) {
    $id = $user['id'];
    try {
        // check if row with given id exists
        if (!IdExistsPdo($conn, $id, 'jc_users')) {
            throw new Exception("User record with id: $id not found", 404);
        }
        // if id exists, perform update
        $sql = 'UPDATE jc_users SET username = ?, email = ?, phone = ?, `current_location` = ?, `current_task` = ? WHERE id = ?;';
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $user['username'], 
            $user['email'], 
            $user['phone'], 
            $user['current_location'], 
            $user['current_task'], 
            $id
        ]);
        $updatedUser = queryRowPdo($conn, 'Get updated user', "SELECT * FROM jc_users WHERE id = ?;", $id); 
        return $updatedUser;
    } catch (Exception $e) {
        $logger->error('Error updating user', ['message'=>$e->getMessage()]);
        throw new Exception('Error updating user: '.$e->getMessage(), 500);
    }
}
function updateUserRole(PDO $conn, int $id, string $role, Logger $logger) {
    if(!isAuthorised(3)) {
        throw new Exception("You are not authorised for this operation!", 401);
    }
    try {
        // check if row with given id exists
        if (!IdExistsPdo($conn, $id, 'jc_users')) {
            throw new Exception("User record with id: $id not found", 404);
        }
        // if id exists, perform update
        $sql = 'UPDATE jc_users SET `role` = ? WHERE id = ?;';
        $stmt = $conn->prepare($sql);
        $stmt->execute([$role, $id]);
        return true;
    } catch (Exception $e) {
        $logger->error('Error updating user role', ['message'=>$e->getMessage()]);
        throw new Exception('Error updating user role: '.$e->getMessage(), 500);
    }
}