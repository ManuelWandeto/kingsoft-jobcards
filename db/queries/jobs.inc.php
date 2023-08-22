<?php
require_once(__DIR__ . '/../functions.inc.php');
require_once('tags.inc.php');
require_once('files.inc.php');
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
            $files[] = getFileInfo(UPLOAD_PATH . $path);
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
                $filepath = UPLOAD_PATH . $path;
                try {
                    $files[] = getFileInfo($filepath);
                } catch (Exception $e) {
                    if($e->getCode() == 404) {
                        deleteAttachment($conn, $filepath);
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
        $sql = 'INSERT INTO jc_attachments (`jobcard_id`, `file_path`) VALUES (?, ?);';
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
            $files[] = getFileInfo(UPLOAD_PATH . $path);
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