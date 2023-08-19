<?php
session_start();
define('UPLOAD_PATH', '../uploads/');
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
        throw new Exception("Error preparing sql statement: ".mysqli_stmt_error($stmt), 500);
    }
    if (!mysqli_stmt_bind_param($stmt, $paramTypes, ...$params)) {
        throw new Exception("Error binding parameters to $queryName: ".mysqli_stmt_error($stmt), 400);
    }
    if(!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing $queryName query: ".mysqli_stmt_error($stmt), 500);
    }

    $result = mysqli_stmt_get_result($stmt);
    if(!$result) {
        throw new Exception("Error getting query results: ".mysqli_stmt_error($stmt), 500);
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

function uploadFiles() {
    if (!isset($_FILES['attachments']['name'])) {
        throw new Exception("attachments is not set", 500);
    }
    $files = array_filter($_FILES['attachments']['name']);
    $paths = [];
    $total = count($files);
    if(!$total) {
        return false;
    }
    for ($i=0; $i < $total; $i++) { 
        if($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
            $upload_dir = UPLOAD_PATH . 'user_'. $_SESSION['user_id'] . '/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $filename = $_FILES['attachments']['name'][$i];
            $tempPath = $_FILES['attachments']['tmp_name'][$i];
            $filepath = $upload_dir . basename($_FILES['attachments']['name'][$i]);
            $acceptedTypes = array('doc', 'docx', 'pdf', 'csv', 'xlsx');
            $fileType = strtolower(pathinfo($_FILES['attachments']['name'][$i], PATHINFO_EXTENSION));
            // check if file already exists
            if (file_exists($filepath)) {
                unlink($filepath);
            };
            // check if file is not of an accepted type
            if (!in_array($fileType, $acceptedTypes) && strpos($_FILES['attachments']['type'][$i], 'image/') !== 0) {
                throw new Exception("$filename is not of an accepted type, only documents and images", 400);
            }
            if ($tempPath) {
                if(move_uploaded_file($tempPath, $filepath)) {
                    $paths[] = $filepath;
                } else {
                    return false;
                }
            } else {
                throw new Exception("error reading temp path of file", 500);
            }
        } else {
            $file = $_FILES['attachments']['name'][$i];

            switch ($_FILES['attachments']['error'][$i]) {
                case UPLOAD_ERR_INI_SIZE:
                    throw new Exception("$file exceeds max allowed size", 400);

                case UPLOAD_ERR_PARTIAL:
                    throw new Exception("$file was only partially uploaded", 500);

                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new Exception("$file missing a temporary folder", 500);

                case UPLOAD_ERR_NO_FILE:
                    throw new Exception("$file was not uploaded", 500);
                    
                case UPLOAD_ERR_CANT_WRITE:
                    throw new Exception("Failed to write $file to disk", 500);
            
                default:
                    throw new Exception("Uncaught upload error", 500);
            }
        }
    }
    return $paths;
}

function deleteUploadedFile(mysqli $conn, string $filepath) {
    $filename = basename($filepath);
    
    if (!file_exists($filepath)) {
        throw new Exception("$filename doesn't exist", 404);
    }
    if (!strpos(dirname($filepath), 'user_'. $_SESSION['user_id'])) {
        throw new Exception("Unauthorised deletion, you can only delete files you posted", 401);
    }
    deleteAttachment($conn, $filepath);
    unlink($filepath);
    return true;
}

function deleteAttachment(mysqli $conn, string $filepath) {
    $sql = "DELETE FROM jc_attachments WHERE `file_path` = ?;";
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt, $sql)) {
        throw new Exception("Error preparing delete attachment query: ".mysqli_stmt_error($stmt), 500);
    }
    if (!mysqli_stmt_bind_param($stmt, 's', $filepath)) {
        throw new Exception("Error binding params to delete attachment query: ".mysqli_stmt_error($stmt), 400);
    }
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing delete attachment query: ".mysqli_stmt_error($stmt), 500);
    }
    mysqli_stmt_close($stmt);
}
function getFileInfo(string $filepath) {
    $filename = basename($filepath);
    if (!file_exists($filepath)) {
        throw new Exception("Error getting file info: $filename doesn't exist", 404);
    }
    $filesize = filesize($filepath);
    $filetype = mime_content_type($filepath);
    $lastModified = filemtime($filepath);
    return [
        "name" => $filename,
        "size" => $filesize,
        "type" => $filetype,
        "db_path" => $filepath,
        "lastModified" => $lastModified
    ];
}
function addJob(mysqli $conn, array $jobData) {
    $filepaths = [];
    try {
        $filepaths = uploadFiles();
    } catch (Exception $e) {
        throw $e;
    }
    
    $sql = 'INSERT INTO jc_jobcards 
                (
                    `project`, 
                    client_id, 
                    `priority`,  
                    `assigned_to`, 
                    `supervised_by`, 
                    `description`, 
                    `location`, 
                    `status`, 
                    `start_date`, 
                    `end_date`, 
                    completion_notes, 
                    issues_arrising
                ) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);';
    $stmt = mysqli_stmt_init($conn);
    if(!mysqli_stmt_prepare($stmt, $sql)) {
        throw new Exception("Error preparing sql statement: ". mysqli_stmt_error($stmt), 500);
    }
    $boundOk = mysqli_stmt_bind_param(
        $stmt, 
        'sisiisssssss', 
        $jobData['project'], 
        $jobData['client_id'], 
        $jobData['priority'], 
        $jobData['assigned_to'], 
        $jobData['supervised_by'], 
        $jobData['description'], 
        $jobData['location'], 
        $jobData['status'], 
        $jobData['start_date'], 
        $jobData['end_date'],
        $jobData['completion_notes'],
        $jobData['issues_arrising'],
    );
    if (!$boundOk) {
        throw new Exception("Invalid params: ".mysqli_stmt_error($stmt), 400);
    }
    if(!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing insert job query: ".mysqli_stmt_error($stmt), 500);
    }
    mysqli_stmt_close($stmt);
    $lastInsertId = mysqli_insert_id($conn);
    if ($filepaths) {
        // IF attachments have been uploaded, post them to the attachments table
        $sql = 'INSERT INTO jc_attachments (`jobcard_id`, `file_path`) VALUES (?, ?)';
        $stmt = mysqli_stmt_init($conn);
        if(!mysqli_stmt_prepare($stmt, $sql)) {
            throw new Exception("Error preparing sql statement: ". mysqli_stmt_error($stmt), 500);
        }
        for ($i=0; $i < count($filepaths); $i++) { 
            $boundOk = mysqli_stmt_bind_param($stmt, 'is', $lastInsertId, $filepaths[$i]);
            if (!$boundOk) {
                throw new Exception("Invalid params: ".mysqli_stmt_error($stmt), 400);
            }
            if(!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error executing insert attachment query: ".mysqli_stmt_error($stmt), 500);
            }
        }
        mysqli_stmt_close($stmt);
    }
    $tags = $jobData['tags'][0] ? array_map('intval', explode(',', $jobData['tags'][0])) : [];

    if ($tags) {
        $sql = 'INSERT INTO jc_jobcard_tags (`jobcard_id`, `tag_id`) VALUES (?, ?)';
        $stmt = mysqli_stmt_init($conn);
        if(!mysqli_stmt_prepare($stmt, $sql)) {
            throw new Exception("Error preparing sql statement: ". mysqli_stmt_error($stmt), 500);
        }
        for ($i=0; $i < count($tags); $i++) { 
            $boundOk = mysqli_stmt_bind_param($stmt, 'ii', $lastInsertId, $tags[$i]);
            if (!$boundOk) {
                throw new Exception("Invalid params: ".mysqli_stmt_error($stmt), 400);
            }
            if(!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error executing insert tags query: ".mysqli_stmt_error($stmt), 500);
            }
        }
        mysqli_stmt_close($stmt);
    }
    $getNewJob = "
        SELECT 
            j.id, 
            j.project, 
            j.client_id,
            j.priority,
            j.assigned_to,
            j.supervised_by,
            j.description,
            j.`location`,
            j.status,
            j.start_date,
            j.end_date,
            j.completion_notes,
            j.issues_arrising,
            GROUP_CONCAT(DISTINCT a.file_path) as files,
            GROUP_CONCAT(DISTINCT t.tag_id) as tags,
            j.created_at
        FROM jc_jobcards as j
        LEFT JOIN jc_attachments as a
        ON j.id = a.jobcard_id
        LEFT JOIN jc_jobcard_tags as t
        ON j.id = t.jobcard_id
        WHERE j.id = ?
        GROUP BY j.id;
    ";
    $newJob = queryRow($conn, 'get inserted job', $getNewJob, 'i', $lastInsertId); 
    if (!$newJob) {
        throw new Exception("Error getting inserted job", 500);
    }
    if($newJob['files']) {
        $files = [];
        $paths = explode(',', $newJob['files']);
        foreach ($paths as $path) {
            $files[] = getFileInfo($path);
        }
        $newJob['files'] = $files;
    }
    if($newJob['tags']) {
        $tagIdsArray = array_map('intval', explode(',', $newJob['tags']));
        $tagsArray = [];
        for ($i=0; $i < count($tagIdsArray); $i++) { 
            $tagsArray[] = getTagInfo($conn, $tagIdsArray[$i]);
        }
        $newJob['tags'] = $tagsArray;
    }
    return $newJob;
}
function getJobs(mysqli $conn) {
    // run update_jobcard_statuses() stored procedure
    if(!mysqli_query($conn, 'CALL update_jobcard_statuses();')) {
        throw new Exception("Error running jobcards maintenance ", 500);
    }

    $results = mysqli_query(
        $conn, 
        "
        SELECT 
            j.id, 
            j.project, 
            j.client_id,
            j.priority,
            j.assigned_to,
            j.supervised_by,
            j.description,
            j.`location`,
            j.status,
            j.start_date,
            j.end_date,
            j.completion_notes,
            j.issues_arrising,
            GROUP_CONCAT(DISTINCT a.file_path) as files,
            GROUP_CONCAT(DISTINCT t.tag_id) as tags,
            j.created_at
        FROM jc_jobcards as j
        LEFT JOIN jc_attachments as a
        ON j.id = a.jobcard_id
        LEFT JOIN jc_jobcard_tags as t
        ON j.id = t.jobcard_id
        GROUP BY j.id
        ORDER BY priority DESC, status DESC, created_at ASC;
        "
    );
    $jobs = [];
    if (!$results) {
        throw new Exception("Error getting jobs", 500);
    }
    if (mysqli_num_rows($results) === 0) {
        return $jobs;
    }
    while ($row = mysqli_fetch_assoc($results)) {
        if($row['files']) {
            $files = [];
            $paths = explode(',', $row['files']);
            foreach ($paths as $path) {
                try {
                    $files[] = getFileInfo($path);
                } catch (Exception $e) {
                    if($e->getCode() == 404) {
                        deleteAttachment($conn, $path);
                        continue;
                    }
                    throw $e;
                }
            }
            $row['files'] = $files;
        }
        if($row['tags']) {
            $tagIdsArray = array_map('intval', explode(',', $row['tags']));
            $tagsArray = [];
            for ($i=0; $i < count($tagIdsArray); $i++) { 
                $tagsArray[] = getTagInfo($conn, $tagIdsArray[$i]);
            }
            $row['tags'] = $tagsArray;
        }
        $jobs[] = $row;
    }

    return $jobs;
}

function updateJob(mysqli $conn, array $job) {
    $filepaths = [];
    try {
        $filepaths = uploadFiles();
    } catch (Exception $e) {
        throw $e;
    }

    $id = $job['id'];
    // check if row with given id exists
    if (!IdExists($conn, $id, 'jc_jobcards')) {
        throw new Exception("Job record with id: $id not found", 404);
    }
    $assignee = htmlspecialchars(trim($job['assigned_to']," ;$\n\r\t\v\x00")) ?? null;
    $supervisor = htmlspecialchars(trim($job["supervised_by"]," ;$\n\r\t\v\x00")) ?? null;
    
    $sql = "UPDATE jc_jobcards 
            SET     
                `project` = ?, 
                client_id = ?, 
                `priority` = ?,  
                `assigned_to` = $assignee, 
                `supervised_by` = $supervisor, 
                `description` = ?, 
                `location` = ?, 
                `status` = ?, 
                `start_date` = ?, 
                `end_date` = ?, 
                completion_notes = ?, 
                issues_arrising = ?
            WHERE id = ?;";
    $stmt = mysqli_stmt_init($conn);
    if(!mysqli_stmt_prepare($stmt, $sql)) {
        throw new Exception("Error preparing sql statement: ". mysqli_stmt_error($stmt), 500);
    }
    if (!mysqli_stmt_bind_param(
            $stmt, 
            'sissssssssi', 
            $job['project'], 
            $job['client_id'], 
            $job['priority'], 
            $job['description'], 
            $job['location'], 
            $job['status'], 
            $job['start_date'], 
            $job['end_date'],
            $job['completion_notes'],
            $job['issues_arrising'],
            $id
        )) {
        throw new Exception("Invalid params: ". mysqli_stmt_error($stmt), 400);
    }
    if(!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing update job query: ". mysqli_stmt_error($stmt), 500);
    }
    mysqli_stmt_close($stmt);
    if ($filepaths) {
        // IF attachments have been uploaded, post them to the attachments table
        $sql = 'INSERT INTO jc_attachments (`jobcard_id`, `file_path`) VALUES (?, ?)';
        $stmt = mysqli_stmt_init($conn);
        if(!mysqli_stmt_prepare($stmt, $sql)) {
            throw new Exception("Error preparing sql statement: ". mysqli_stmt_error($stmt), 500);
        }
        for ($i=0; $i < count($filepaths); $i++) { 
            $boundOk = mysqli_stmt_bind_param($stmt, 'is', $id, $filepaths[$i]);
            if (!$boundOk) {
                throw new Exception("Invalid params: ".mysqli_stmt_error($stmt), 400);
            }
            if(!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error executing insert attachment query: ".mysqli_stmt_error($stmt), 500);
            }
        }
        mysqli_stmt_close($stmt);
    }
    $oldTagsRecord = queryRow(
        $conn, 
        'get tags for jobcard', 
        "SELECT GROUP_CONCAT(t.tag_id) as tags FROM jc_jobcard_tags t WHERE  t.jobcard_id = ?;", 
        'i',
        $id
    );

    $oldTags = $oldTagsRecord['tags'] ? array_map('intval', explode(',', $oldTagsRecord['tags'])) : [];

    $newTags = $job['tags'][0] ? array_map('intval', explode(',', $job['tags'][0])) : [];
    // Get old tags not included in the new and remove them
    $removedTags = array_values(array_diff($oldTags, $newTags));

    if($removedTags) {
        $sql = 'DELETE FROM jc_jobcard_tags WHERE jobcard_id = ? AND tag_id = ?;';
        $stmt = mysqli_stmt_init($conn);
        if(!mysqli_stmt_prepare($stmt, $sql)) {
            throw new Exception("Error preparing sql statement: ". mysqli_stmt_error($stmt), 500);
        }
        for ($i=0; $i < count($removedTags); $i++) { 
            $boundOk = mysqli_stmt_bind_param($stmt, 'ii', $id, $removedTags[$i]);
            if (!$boundOk) {
                throw new Exception("Invalid params: ".mysqli_stmt_error($stmt), 400);
            }
            if(!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error executing detach removed tags query: ".mysqli_stmt_error($stmt), 500);
            }
        }
        mysqli_stmt_close($stmt);
    }
    // get new tags not already in the old and add them
    $tags = array_values(array_diff($newTags, $oldTags));
    
    if ($tags) {
        $sql = 'INSERT INTO jc_jobcard_tags (`jobcard_id`, `tag_id`) VALUES (?, ?);';
        $stmt = mysqli_stmt_init($conn);
        if(!mysqli_stmt_prepare($stmt, $sql)) {
            throw new Exception("Error preparing sql statement: ". mysqli_stmt_error($stmt), 500);
        }
        for ($i=0; $i < count($tags); $i++) { 
            $boundOk = mysqli_stmt_bind_param($stmt, 'ii', $id, $tags[$i]);
            if (!$boundOk) {
                throw new Exception("Invalid params: ".mysqli_stmt_error($stmt), 400);
            }
            if(!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error executing insert tags query: ".mysqli_stmt_error($stmt), 500);
            }
        }
        mysqli_stmt_close($stmt);
    }
    $getUpdatedJob = "
        SELECT 
            j.id, 
            j.project, 
            j.client_id,
            j.priority,
            j.assigned_to,
            j.supervised_by,
            j.description,
            j.`location`,
            j.status,
            j.start_date,
            j.end_date,
            j.completion_notes,
            j.issues_arrising,
            GROUP_CONCAT(DISTINCT a.file_path) as files,
            GROUP_CONCAT(DISTINCT t.tag_id) as tags,
            j.created_at
        FROM jc_jobcards as j
        LEFT JOIN jc_attachments as a
        ON j.id = a.jobcard_id
        LEFT JOIN jc_jobcard_tags as t
        ON j.id = t.jobcard_id
        WHERE j.id = ?
        GROUP BY j.id;
    ";
    $updatedJob = queryRow($conn, 'get inserted job', $getUpdatedJob, 'i', $id); 
    if (!$updatedJob) {
        throw new Exception("Error getting inserted job", 500);
    }
    if($updatedJob['files'] ) {
        $files = [];
        $paths = explode(',', $updatedJob['files']);
        foreach ($paths as $path) {
            $files[] = getFileInfo($path);
        }
        $updatedJob['files'] = $files;
    }
    if($updatedJob['tags']) {
        $tagIdsArray = array_map('intval', explode(',', $updatedJob['tags']));
        $tagsArray = [];
        for ($i=0; $i < count($tagIdsArray); $i++) { 
            $tagsArray[] = getTagInfo($conn, $tagIdsArray[$i]);
        }
        $updatedJob['tags'] = $tagsArray;
    }
    return $updatedJob;
}
function finaliseJob(mysqli $conn, array $job) {
    $id = $job['id'];
    // check if row with given id exists
    if (!IdExists($conn, $id, 'jc_jobcards')) {
        throw new Exception("Job record with id: $id not found", 404);
    }
    // if id exists, perform update
    $sql = 'UPDATE jc_jobcards 
            SET     
                `status` = ?, 
                completion_notes = ?, 
                issues_arrising = ?
            WHERE id = ?;';
    $stmt = mysqli_stmt_init($conn);
    if(!mysqli_stmt_prepare($stmt, $sql)) {
        throw new Exception("Error preparing sql statement: ". mysqli_stmt_error($stmt), 500);
    }
    if (!mysqli_stmt_bind_param(
            $stmt, 
            'sssi', 
            $job['status'], 
            $job['completion_notes'],
            $job['issues_arrising'],
            $id
        )) {
        throw new Exception("Invalid params: ". mysqli_stmt_error($stmt), 400);
    }
    if(!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing insert client query: ". mysqli_stmt_error($stmt), 500);
    }
    mysqli_stmt_close($stmt);
    return $job;
}

function addTag(mysqli $conn, array $tag) {
    if (!isAuthorised(2)) {
        throw new Exception("You are not authorised for this operation!", 401);
    }
    $label = $tag['label'];
    $color = $tag['colorcode'];
    if(queryRow($conn, 'tag exists', 
        "SELECT * FROM `jc_tags` WHERE `label` = ? OR `colorcode` = ?;", 'ss', $label, $color)
    ) {
        throw new Exception("A tag with that label or color already exists", 400);
    }
    $sql = 'INSERT INTO jc_tags 
                (
                    `label`, 
                    colorcode
                ) 
            VALUES (?, ?);';
    $stmt = mysqli_stmt_init($conn);
    if(!mysqli_stmt_prepare($stmt, $sql)) {
        throw new Exception("Error preparing sql statement: ". mysqli_stmt_error($stmt), 500);
    }
    $boundOk = mysqli_stmt_bind_param(
        $stmt, 
        'ss', 
        $label,
        $color
    );
    if (!$boundOk) {
        throw new Exception("Invalid params: ".mysqli_stmt_error($stmt), 400);
    }
    if(!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing insert tag query: ".mysqli_stmt_error($stmt), 500);
    }
    mysqli_stmt_close($stmt);
    $lastInsertId = mysqli_insert_id($conn);
    $newTag = IdExists($conn, $lastInsertId, 'jc_tags');
    return $newTag;
}
function getTagInfo($conn, int $id) {
    return queryRow(
        $conn, 
        'get tag by Id', 
        "SELECT * FROM jc_tags WHERE id = ?;",
        'i',
        $id
    );
}
function getTags(mysqli $conn) {
    $results = mysqli_query($conn, "SELECT * FROM jc_tags;");
    if (!$results) {
        throw new Exception("Error getting tags");
    }
    $tags = array();
    while ($row = mysqli_fetch_assoc($results)) {
        $tags[] = $row;
    }
    return $tags;
}

function updateTag(mysqli $conn, array $tag) {
    if (!isAuthorised(2)) {
        throw new Exception("You are not authorised for this operation!", 401);
    }
    $id = $tag['id'];
    // check if row with given id exists
    if (!IdExists($conn, $id, 'jc_tags')) {
        throw new Exception("tag with id: $id not found", 404);
    }
    // if id exists, perform update
    $sql = 'UPDATE jc_tags SET `label` = ?, `colorcode` = ? WHERE id = ?;';
    $stmt = mysqli_stmt_init($conn);
    if(!mysqli_stmt_prepare($stmt, $sql)) {
        throw new Exception("Error preparing sql statement: ". mysqli_stmt_error($stmt), 500);
    }
    if (!mysqli_stmt_bind_param($stmt, 'ssi', $tag['label'], $tag['colorcode'], $id)) {
        throw new Exception("Invalid params: ". mysqli_stmt_error($stmt), 400);
    }
    if(!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing update tag query: ". mysqli_stmt_error($stmt), 500);
    }
    mysqli_stmt_close($stmt);
    $updatedTag = IdExists($conn, $id, 'jc_tags');
    return $updatedTag;
}

function deleteTag(mysqli $conn, int $id) {
    if (!isAuthorised(2)) {
        throw new Exception("You are not authorised for this operation!", 401);
    }
    // check if row with given id exists
    if (!IdExists($conn, $id, 'jc_tags')) {
        throw new Exception("tag record with id: $id not found", 404);
    }
    // if id exists, perform deletion
    $sql = 'DELETE FROM jc_tags WHERE id = ?;';
    $stmt = mysqli_stmt_init($conn);
    if(!mysqli_stmt_prepare($stmt, $sql)) {
        throw new Exception("Error preparing sql statement: ". mysqli_stmt_error($stmt), 500);
    }
    if (!mysqli_stmt_bind_param($stmt, 'i', $id)) {
        throw new Exception("Invalid params: ". mysqli_stmt_error($stmt), 400);
    }
    if(!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing delete tag query:". mysqli_stmt_error($stmt), 500);
    }
    mysqli_stmt_close($stmt);
    return true;
}