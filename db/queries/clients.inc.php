<?php
require_once(__DIR__ . '/../functions.inc.php');
require_once('files.inc.php');
require_once(__DIR__ . '/../../utils/constants.php');
define('LOGO_PATH', UPLOAD_PATH . 'client_logos' . DIRECTORY_SEPARATOR);
function addClient(PDO $conn, array $client) {
    if (!isAuthorised(2)) {
        throw new Exception("You are not authorised for this operation!", 401);
    }
    $logo = null;
    try {
        if(!isset($_FILES['logo']['name'])) {
            throw new Exception("Logo is not set", 400);
        }
        $file = $_FILES['logo']['name'];
        if ($file && strpos($_FILES['logo']['type'], 'image/') !== 0) {
            throw new Exception("$file is not of an accepted logo type, only images", 400);
        }
        $logo = uploadFile('logo', LOGO_PATH) ?? null;
    } catch (Exception $e) {
        throw $e;
    }
    $clientName = $client['name'];
    $clientExists = queryRowPdo($conn, "client exists", "SELECT * FROM jc_clients WHERE `name` = ?;", $clientName);
    if ($clientExists) {
        throw new Exception("client with name $clientName already exists", 400);
    }
    $sql = 
    'INSERT INTO jc_clients (
        `name`, 
        `email`, 
        `contact_person`, 
        `phone`, 
        `location`, 
        `logo`, 
        `last_update_date`, 
        `last_update_exe`
    ) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?);';
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $clientName, 
            $client['email'], 
            $client['contact_person'], 
            $client['phone'], 
            $client['location'], 
            $logo, 
            strlen($client['last-update-date']) ? $client['last-update-date'] : null, 
            strlen($client['last-update-exe']) ? $client['last-update-exe'] : null
        ]);
        $lastInsertId = $conn->lastInsertId();
        $getClient = $conn->query("SELECT * FROM jc_clients WHERE id = $lastInsertId");

        $newClient = $getClient->fetch(PDO::FETCH_ASSOC);
        return $newClient;
    } catch (PDOException $e) {
        throw new Exception('Error with Add client query: '.$e->getMessage(), 500);
    }
}

function updateClient(PDO $conn, array $client) {
    if (!isAuthorised(2)) {
        throw new Exception("You are not authorised for this operation!", 401);
    }
    $id = $client['id'];
    // check if row with given id exists
    $oldClient = IdExistsPdo($conn, $id, 'jc_clients');
    if (!$oldClient) {
        throw new Exception("Client record with id: $id not found", 404);
    }
    $logo = null;

    if(isset($_FILES['logo']['name']) && strlen($_FILES['logo']['name'])) {
        // upload new logo
        $filename = $_FILES['logo']['name'];
        try {
            if ($filename && strpos($_FILES['logo']['type'], 'image/') !== 0) {
                throw new Exception("$filename is not of an accepted logo type, only images", 400);
            }
            $logo = uploadFile('logo', LOGO_PATH) ?? null;
        } catch (Exception $e) {
            throw $e;
        }
        // if old client had logo, delete it from file system
        if(strlen($oldClient['logo']) && $oldClient['logo'] !== $filename) {
            $previousLogo = LOGO_PATH . $oldClient['logo'];
            if(file_exists($previousLogo)) {
                unlink($previousLogo);
            }
        }
    }
    // if id exists, perform update
    $sql = 
        'UPDATE jc_clients 
            SET 
            `name` = ?, 
            `email` = ?, 
            `contact_person` = ?, 
            `phone` = ?, 
            `location` = ?, 
            `logo` = ?,
            `last_update_date` = ?,
            `last_update_exe` = ?
        WHERE id = ?;';
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $client['name'], 
            $client['email'], 
            $client['contact_person'], 
            $client['phone'], 
            $client['location'], 
            $logo, 
            strlen($client['last-update-date']) ? $client['last-update-date'] : null, 
            strlen($client['last-update-exe']) ? $client['last-update-exe'] : null,
            $id
        ]);
        return queryRowPdo($conn, 'get updated client', 'SELECT * FROM `jc_clients` WHERE id = ?;', $id);
    } catch (PDOException $e) {
        throw new Exception('Error with Update client query: '.$e->getMessage(), 500);
    }
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
        if(isset($row['logo']) && strlen($row['logo'])) {
            $logo = LOGO_PATH . $row['logo'];
            if(!file_exists($logo)) {
                $id = $row['id'];
                mysqli_query($conn, "UPDATE `jc_clients` SET `logo` = NULL WHERE `id` = $id;");
                $row['logo'] = null;
            }
        }
        $clients[] = $row;
    }
    return $clients;
}

function deleteLogo(mysqli $conn, array $data) {
    $filepath = LOGO_PATH . $data['filename'];

    if(!file_exists($filepath)) {
        throw new Exception('Logo not found', 404);
    }
    unlink($filepath);
    return queryExec(
        $conn, 
        'delete logo', 
        "UPDATE `jc_clients` SET `logo` = NULL WHERE `logo` = ? AND id = ?;", 
        'si', 
        $data['filename'], 
        $data['clientId']
    );
}