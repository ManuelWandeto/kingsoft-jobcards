<?php
require_once(__DIR__ . '/../functions.inc.php');

function addClient(mysqli $conn, array $client) {
    if (!isAuthorised(2)) {
        throw new Exception("You are not authorised for this operation!", 401);
    }
    $clientName = $client['name'];
    $clientExists = queryRow($conn, "client exists", "SELECT * FROM jc_clients WHERE `name` = ?;", 's', $clientName);
    if ($clientExists) {
        throw new Exception("client with name $clientName already exists", 400);
    }
    $sql = 'INSERT INTO jc_clients (`name`, email, `phone`, `location`) VALUES (?, ?, ?, ?);';
    $stmt = mysqli_stmt_init($conn);
    if(!mysqli_stmt_prepare($stmt, $sql)) {
        throw new Exception("Error preparing sql statement: ". mysqli_stmt_error($stmt), 500);
    }
    $boundOk = mysqli_stmt_bind_param($stmt, 'ssss', $clientName, $client['email'], $client['phone'], $client['location']);
    if(!$boundOk) {
        throw new Exception("Invalid params: ".mysqli_stmt_error($stmt), 400);
    }
    if(!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing insert client query: ".mysqli_stmt_error($stmt), 500);
    }
    mysqli_stmt_close($stmt);
    $lastInsertId = mysqli_insert_id($conn);
    $lastInsertRow = mysqli_query($conn, "SELECT * FROM jc_clients WHERE id = $lastInsertId");
    if (!$lastInsertRow) {
        throw new Exception("Error getting last insert row", 500);
    }
    $newClient = mysqli_fetch_assoc($lastInsertRow);
    if (!$newClient) {
        throw new Exception("Error getting associative array from last insert row", 500);
    }
    return $newClient;
}

function updateClient(mysqli $conn, array $client) {
    if (!isAuthorised(2)) {
        throw new Exception("You are not authorised for this operation!", 401);
    }
    $id = $client['id'];
    // check if row with given id exists
    if (!IdExists($conn, $id, 'jc_clients')) {
        throw new Exception("Client record with id: $id not found", 404);
    }
    // if id exists, perform update
    $sql = 'UPDATE jc_clients SET `name` = ?, `email` = ?, `phone` = ?, `location` = ? WHERE id = ?;';
    $stmt = mysqli_stmt_init($conn);
    if(!mysqli_stmt_prepare($stmt, $sql)) {
        throw new Exception("Error preparing sql statement: ". mysqli_stmt_error($stmt), 500);
    }
    if (!mysqli_stmt_bind_param($stmt, 'ssssi', $client['name'], $client['email'], $client['phone'], $client['location'], $id)) {
        throw new Exception("Invalid params: ". mysqli_stmt_error($stmt), 400);
    }
    if(!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing update client query: ". mysqli_stmt_error($stmt), 500);
    }
    mysqli_stmt_close($stmt);
    return $client;
}
function deleteClient(mysqli $conn, int $clientId) {
    if (!isAuthorised(2)) {
        throw new Exception("You are not authorised for this operation!", 401);
    }
    // check if row with given id exists
    if (!IdExists($conn, $clientId, 'jc_clients')) {
        throw new Exception("Client record with id: $clientId not found", 404);
    }
    // if id exists, perform deletion
    $sql = 'DELETE FROM jc_clients WHERE id = ?;';
    $stmt = mysqli_stmt_init($conn);
    if(!mysqli_stmt_prepare($stmt, $sql)) {
        throw new Exception("Error preparing sql statement: ". mysqli_stmt_error($stmt), 500);
    }
    if (!mysqli_stmt_bind_param($stmt, 'i', $clientId)) {
        throw new Exception("Invalid params: ". mysqli_stmt_error($stmt), 400);
    }
    if(!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing delete client query:". mysqli_stmt_error($stmt), 500);
    }
    mysqli_stmt_close($stmt);
    return true;
}
function getClients(mysqli $conn) {
    $results = mysqli_query($conn, "SELECT * FROM jc_clients;");
    $clients = array();
    if (!$results) {
        throw new Exception("Error getting clients", 500);
    }
    if (mysqli_num_rows($results) === 0) {
        return $clients;
    }
    while ($row = mysqli_fetch_assoc($results)) {
        $clients[] = $row;
    }
    return $clients;
}