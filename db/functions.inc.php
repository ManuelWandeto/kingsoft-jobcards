<?php
session_start();
require_once(__DIR__ . '/../utils/constants.php');

function uidExistsPdo(PDO $conn, string $username, string $email) {
    $sql = 'SELECT * FROM `jc_users` WHERE `username` = ? OR `email` = ?;';
    $stmt = $conn->prepare($sql);
    // redirect('../Login/custom-login.php?error=internal+error');
    $stmt->execute([$username, $email]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if($result) {
        return $result;
    }
    return false;
}

function IdExistsPdo(
    PDO $conn, 
    int $id, 
    string $tableName, 
    string $idColumn = 'id'
) {
    try {
        $sql = "SELECT * FROM $tableName WHERE $idColumn = ?;";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if($result) {
            return $result;
        }
        return false;
    } catch (PDOException $e) {
        throw $e;
    }
}
function queryRowPdo(
    PDO $conn, 
    string $queryName,
    string $sql,
    ...$params
) {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function isAuthorised(int $requiredLevel) {
    // admin is level 3, editor = 2, user = 1
    $roles = [
        "USER" => 1,
        "EDITOR" => 2,
        "ADMIN" => 3
    ];
    $role = $_SESSION["role"];
    return $roles[$role] >= $requiredLevel;
}