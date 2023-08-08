<?php
session_start();
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

function IdExists(mysqli $conn, int $id, string $tableName, string $idColumn = 'id') {
    $stmt = mysqli_stmt_init($conn);
    $sql = "SELECT * FROM $tableName WHERE $idColumn = ?;";
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
    if(uidExists($conn, $user["username"], $user["email"])) {
        throw new Exception("This user already exists!", 400);
    }
    $sql = 'INSERT INTO jc_users (username, email, role, password, created_at) VALUES (?, ?, ?, ?, null);';
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
    $sql = 'INSERT INTO jc_clients (`name`, email, `phone`, `location`, created_at) VALUES (?, ?, ?, ?, null);';
    $stmt = mysqli_stmt_init($conn);
    if(!mysqli_stmt_prepare($stmt, $sql)) {
        throw new Exception("Error preparing sql statement: ". mysqli_stmt_error($stmt), 500);
    }
    $boundOk = mysqli_stmt_bind_param($stmt, 'ssss', $client['name'], $client['email'], $client['phone'], $client['location']);
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

function addJob(mysqli $conn, array $jobData) {
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
                    issues_arrising,
                    created_at
                ) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, null);';
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
        throw new Exception("Error executing insert client query: ".mysqli_stmt_error($stmt), 500);
    }
    mysqli_stmt_close($stmt);
    $lastInsertId = mysqli_insert_id($conn);
    $newJob = IdExists($conn, $lastInsertId, 'jc_jobcards');
    if (!$newJob) {
        throw new Exception("Error getting inserted job", 500);
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
        "SELECT * FROM jc_jobcards ORDER BY priority DESC, status DESC, created_at ASC;"
    );
    $jobs = [];
    if (!$results) {
        throw new Exception("Error getting jobs", 500);
    }
    if (mysqli_num_rows($results) === 0) {
        return $jobs;
    }
    while ($row = mysqli_fetch_assoc($results)) {
        $jobs[] = $row;
    }
    return $jobs;
}

function updateJob(mysqli $conn, array $job) {
    $id = $job['id'];
    // check if row with given id exists
    if (!IdExists($conn, $id, 'jc_jobcards')) {
        throw new Exception("Job record with id: $id not found", 404);
    }
    // if id exists, perform update
    $sql = 'UPDATE jc_jobcards 
            SET     
                `project` = ?, 
                client_id = ?, 
                `priority` = ?,  
                `assigned_to` = ?, 
                `supervised_by` = ?, 
                `description` = ?, 
                `location` = ?, 
                `status` = ?, 
                `start_date` = ?, 
                `end_date` = ?, 
                completion_notes = ?, 
                issues_arrising = ?
            WHERE id = ?;';
    $stmt = mysqli_stmt_init($conn);
    if(!mysqli_stmt_prepare($stmt, $sql)) {
        throw new Exception("Error preparing sql statement: ". mysqli_stmt_error($stmt), 500);
    }
    if (!mysqli_stmt_bind_param(
            $stmt, 
            'sisiisssssssi', 
            $job['project'], 
            $job['client_id'], 
            $job['priority'], 
            $job['assigned_to'], 
            $job['supervised_by'], 
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
        throw new Exception("Error executing insert client query: ". mysqli_stmt_error($stmt), 500);
    }
    mysqli_stmt_close($stmt);
    $updatedJob = IdExists($conn,  $id, 'jc_jobcards');
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