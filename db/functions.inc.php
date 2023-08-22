<?php
session_start();
require_once(__DIR__ . '/../utils/constants.php');
function uidExists(mysqli $conn, string $username, string $email) {
    $sql = 'SELECT * FROM `jc_users` WHERE `username` = ? OR `email` = ?;';
    $stmt = mysqli_stmt_init($conn);

    if(!mysqli_stmt_prepare($stmt, $sql)) {
        redirect('../Login/custom-login.php?error=internal+error');
    }
    mysqli_stmt_bind_param($stmt, 'ss', $username, $email);

    if(!mysqli_stmt_execute($stmt)) {
        redirect('../Login/custom-login.php?error=internal+error');
    }
    $results = mysqli_stmt_get_result($stmt);
    $result = false;
    $row = mysqli_fetch_assoc($results);
    if($row) {
        $result = $row;
    } else {
        $result = false;
    }
    mysqli_stmt_close($stmt);
    return $result;
}

/**
 * Summary of IdExists
 * @param mysqli $conn
 * @param int $id
 * @param string $tableName
 * @param string $idColumn
 * @throws \Exception
 * @return array|bool
 */
function IdExists(
    mysqli $conn, 
    int $id, 
    string $tableName, 
    string $idColumn = 'id'
) {
    $sql = "SELECT * FROM $tableName WHERE $idColumn = ?;";
    $stmt = mysqli_stmt_init($conn);
    if(!mysqli_stmt_prepare($stmt, $sql)) {
        throw new Exception("Error preparing sql statement: ".mysqli_stmt_error($stmt), 500);
    }
    if (!mysqli_stmt_bind_param($stmt, 'i', $id)) {
        throw new Exception("Error binding parameters: ".mysqli_stmt_error($stmt), 400);
    }
    if(!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing get record by Id query: ".mysqli_stmt_error($stmt), 500);
    }

    $result = mysqli_stmt_get_result($stmt);
    if(!$result) {
        throw new Exception("Error getting query results: ".mysqli_stmt_error($stmt), 500);
    }
    $record = mysqli_fetch_assoc($result);
    $result = false;
    if (is_array($record)) {
        $result = $record;
    }
    mysqli_stmt_close($stmt);
    return $result;
}
function queryRow(
    mysqli $conn, 
    string $queryName,
    string $sql,
    string $paramTypes,
    ...$params
) {
    $stmt = mysqli_stmt_init($conn);
    if(!mysqli_stmt_prepare($stmt, $sql)) {
        throw new Exception("$queryName Error preparing sql statement: ".mysqli_stmt_error($stmt), 500);
    }
    if (!mysqli_stmt_bind_param($stmt, $paramTypes, ...$params)) {
        throw new Exception("Error binding parameters to $queryName: ".mysqli_stmt_error($stmt), 400);
    }
    if(!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing $queryName query: ".mysqli_stmt_error($stmt), 500);
    }

    $result = mysqli_stmt_get_result($stmt);
    if(!$result) {
        throw new Exception("Error getting $queryName results: ".mysqli_stmt_error($stmt), 500);
    }
    $record = mysqli_fetch_assoc($result);
    $result = false;
    if ($record) {
        $result = $record;
    }
    mysqli_stmt_close($stmt);
    return $result;
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