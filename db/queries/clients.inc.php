<?php
use Monolog\Logger;
require_once(__DIR__ . '/../functions.inc.php');
require_once('files.inc.php');
require_once(__DIR__ . '/../../utils/constants.php');
define('LOGO_PATH', UPLOAD_PATH . 'client_logos' . DIRECTORY_SEPARATOR);
function addClient(PDO $conn, array $client, Logger $logger) {
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
        $logger->withName('uploads')->error('Error uploading client logo', ['message'=>$e->getMessage()]);
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
        `logo`
    ) 
    VALUES (?, ?, ?, ?, ?, ?);';
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $clientName, 
            $client['email'], 
            $client['contact_person'], 
            $client['phone'], 
            $client['location'], 
            $logo
        ]);
        $lastInsertId = $conn->lastInsertId();
        $getClient = $conn->query("SELECT * FROM jc_clients WHERE id = $lastInsertId");

        $newClient = $getClient->fetch(PDO::FETCH_ASSOC);
        return $newClient;
    } catch (PDOException $e) {
        $logger->error('Error adding client', ['message' => $e->getMessage()]);
        throw new Exception('Error with Add client query: '.$e->getMessage(), 500);
    }
}

function updateClient(PDO $conn, array $client, Logger $logger) {
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
            $logger->withName('uploads')->error('Error uploading client logo', ['message', $e->getMessage()]);
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
            `logo` = ?
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
            $id
        ]);
        return queryRowPdo($conn, 'get updated client', 'SELECT * FROM `jc_clients` WHERE id = ?;', $id);
    } catch (PDOException $e) {
        $logger->error('Error updating client', ['message'=>$e->getMessage()]);
        throw new Exception('Error with Update client query: '.$e->getMessage(), 500);
    }
}
function deleteClient(PDO $conn, int $clientId, Logger $logger) {
    if (!isAuthorised(2)) {
        throw new Exception("You are not authorised for this operation!", 401);
    }
    try {
        if (!IdExistsPdo($conn, $clientId, 'jc_clients')) {
            throw new Exception("Client record with id: $clientId not found", 404);
        }
        // if id exists, perform deletion
        $sql = 'DELETE FROM jc_clients WHERE id = ?;';
        $stmt = $conn->prepare($sql);
        $stmt->execute([$clientId]);
        return true;
    } catch (Exception $e) {
        $logger->error('Error deleting client', ['message'=>$e->getMessage()]);
        throw new Error('Error deleting client: '. $e->getMessage(), 500);
    }
}
function getClients(PDO $conn, Logger $logger) {
    try {
        $results = $conn->query("SELECT * FROM jc_clients;")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as $row) {
            # code...
            if(isset($row['logo']) && strlen($row['logo'])) {
                $logo = LOGO_PATH . $row['logo'];
                if(!file_exists($logo)) {
                    $id = $row['id'];
                    $conn->query("UPDATE `jc_clients` SET `logo` = NULL WHERE `id` = $id;");
                    $row['logo'] = null;
                }
            }
        }
        return $results;
    } catch (PDOException $e) {
        $logger->critical('Could not get clients', ['message'=>$e->getMessage()]);
        throw new Error('Error getting clients: '. $e->getMessage());
    }
}

function deleteLogo(PDO $conn, array $data, Logger $logger) {
    $filepath = LOGO_PATH . $data['filename'];

    if(!file_exists($filepath)) {
        $logger->withName('uploads')->error('Error deleting client logo', ['message'=>'Logo not found']);
        throw new Exception('Logo not found', 404);
    }
    unlink($filepath);
    try {
        $stmt = $conn->prepare("UPDATE `jc_clients` SET `logo` = NULL WHERE `logo` = ? AND id = ?;");
        $stmt->execute([
            $data['filename'], 
            $data['clientId']
        ]);
        return true;
    } catch (PDOException $e) {
        $logger->error('Error deleting client logo', ['message'=>$e->getMessage()]);
        throw new Error('Error deleting logo: '.$e->getMessage(), 500);
    }
}