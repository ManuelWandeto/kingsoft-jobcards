<?php
require_once(__DIR__ . '/../functions.inc.php');
require_once('tags.inc.php');
require_once('files.inc.php');
function addJob(mysqli $conn, array $jobData) {
    $filenames = [];
    try {
        $filenames = uploadFiles();
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
                    `reported_by`,
                    `reporter_contacts`,
                    `description`, 
                    `location`, 
                    `status`, 
                    `start_date`, 
                    `end_date`, 
                    completion_notes, 
                    issues_arrising
                ) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);';
    $stmt = mysqli_stmt_init($conn);
    if(!mysqli_stmt_prepare($stmt, $sql)) {
        throw new Exception("Error preparing sql statement: ". mysqli_stmt_error($stmt), 500);
    }
    $boundOk = mysqli_stmt_bind_param(
        $stmt, 
        'sisiisssssssss', 
        $jobData['project'], 
        $jobData['client_id'], 
        $jobData['priority'], 
        $jobData['assigned_to'], 
        $jobData['supervised_by'], 
        $jobData['reported_by'], 
        $jobData['reporter_contacts'], 
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
    if ($filenames) {
        // IF attachments have been uploaded, post them to the attachments table
        $sql = 'INSERT INTO jc_attachments (`jobcard_id`, `file_name`, `uploaded_by`) VALUES (?, ?, ?)';
        $stmt = mysqli_stmt_init($conn);
        if(!mysqli_stmt_prepare($stmt, $sql)) {
            throw new Exception("Error preparing sql statement: ". mysqli_stmt_error($stmt), 500);
        }
        for ($i=0; $i < count($filenames); $i++) { 
            $boundOk = mysqli_stmt_bind_param($stmt, 'isi', $lastInsertId, $filenames[$i], $_SESSION['user_id']);
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
            j.reported_by,
            j.reporter_contacts,
            j.description,
            j.`location`,
            j.status,
            j.start_date,
            j.end_date,
            j.completion_notes,
            j.issues_arrising,
            GROUP_CONCAT(DISTINCT 'user_', a.uploaded_by, '/', a.file_name) as files,
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
            $files[] = getFileInfo(UPLOAD_PATH . join(DIRECTORY_SEPARATOR, explode('/', $path)));
        }
        $newJob['files'] = $files;
    }
    if($newJob['tags']) {
        $newJob['tags'] =  explode(',', $newJob['tags']);
    }
    return $newJob;
}
function getJobs(PDO $conn, array $filters) {
    // run update_jobcard_statuses() stored procedure
    if(!$conn->query('CALL update_jobcard_statuses();')->execute()) {
        throw new Exception("Error running jobcards maintenance ", 500);
    }
    // TODO: Join jobcards with tags to return tag info instead of running separate queries or loading all tags on frontend at once
    $filtersPlaceholder = '';
    $queryParams = array();
    $orderBy = $filters['order-by'] == 'newest' ? 'DESC' : 'ASC';
    if(isset($filters['search-by']) && isset($filters['query']) && strlen($filters['query'])) {
        switch ($filters['search-by']) {
            case 'project':
                $filtersPlaceholder .= ' ' . "WHERE MATCH(j.`project`) AGAINST(:query IN BOOLEAN MODE)";
                $queryParams[':query'] = "'+*{$filters['query']}*'";
                break;
            case 'client':
                $filtersPlaceholder .= ' ' . "WHERE c.`name` LIKE CONCAT('%', :query, '%')";
                $queryParams[':query'] = $filters['query'];        
                break;
            case 'assignee':
                $filtersPlaceholder .= ' ' . "WHERE MATCH(w.`username`) AGAINST(:query IN BOOLEAN MODE)";
                $queryParams[':query'] = "'+*{$filters['query']}*'";
                break;
            case 'supervisor':
                $filtersPlaceholder .= ' ' . "WHERE MATCH(s.`username`) AGAINST(:query IN BOOLEAN MODE)";
                $queryParams[':query'] = "'+*{$filters['query']}*'";
                break;
            case 'location':
                $filtersPlaceholder .= ' ' . "WHERE MATCH(j.`location`) AGAINST(:query IN BOOLEAN MODE)";
                $queryParams[':query'] = "'+*{$filters['query']}*'";
                break;
            case 'description':
                $filtersPlaceholder .= ' ' . "WHERE MATCH(j.`description`) AGAINST(:query IN BOOLEAN MODE)";
                $queryParams[':query'] = "'+*{$filters['query']}*'";
                break;
            default:
                throw new Exception("Invalid search-by parameter", 400);
        }
    }

    if(isset($filters['priority']) && strlen(trim($filters['priority']))) {
        $selectedPriorities = explode(',' , $filters['priority']);
        $priorityKeys = [];
        for ($i=0; $i < count($selectedPriorities); $i++) { 
            $key = ':pr_'.$i;
            $priorityKeys[] = $key;
            $queryParams[$key] = $selectedPriorities[$i];
        }
        $params = implode(',', $priorityKeys);
        $filtersPlaceholder .= $filtersPlaceholder ? "AND j.`priority` IN ($params)" : "WHERE j.`priority` IN ($params)";
    }
    if(isset($filters['status']) && strlen(trim($filters['status']))) {
        $selectedStatuses = explode(',', $filters['status']);
        $statusKeys = [];
        for ($i=0; $i < count($selectedStatuses); $i++) { 
            $key = ':st_'.$i;
            $statusKeys[] = $key;
            $queryParams[$key] = $selectedStatuses[$i];
        }
        $params = implode(',', $statusKeys);
        $filtersPlaceholder .= $filtersPlaceholder ? "AND j.`status` IN ($params)" : "WHERE j.`status` IN ($params)";
    }
    if(isset($filters['tags']) && strlen(trim($filters['tags']))) {
        $selectedTags = explode(',', $filters['tags']);
        $tagKeys = [];
        for ($i=0; $i < count($selectedTags); $i++) { 
            $key = ':tg_'.$i;
            $tagKeys[] = $key;
            $queryParams[$key] = $selectedTags[$i];
        }
        $params = implode(',', $tagKeys);
        $filtersPlaceholder .= $filtersPlaceholder ? "AND t.`tag_id` IN ($params)" : "WHERE t.`tag_id` IN ($params)";
    }
    if(isset($filters['from']) && !isset($filters['to'])) {
        $conjunction = trim($filtersPlaceholder) ? 'AND' : 'WHERE';
        
        $filtersPlaceholder .= "$conjunction j.`end_date` > :due_from";

        $queryParams['due_from'] = $filters['from'];
    }
    if(!isset($filters['from']) && isset($filters['to'])) {
        $conjunction = trim($filtersPlaceholder) ? 'AND' : 'WHERE';

        $filtersPlaceholder .= "$conjunction j.`end_date` < :due_till";

        $queryParams['due_till'] = $filters['to'];
    }
    if(isset($filters['from']) && isset($filters['to'])) {
        $conjunction = trim($filtersPlaceholder) ? 'AND' : 'WHERE';

        $filtersPlaceholder .= "$conjunction j.`end_date` BETWEEN :due_from AND :due_till";

        $queryParams['due_from'] = $filters['from'];
        $queryParams['due_till'] = $filters['to'];
    }
    $pagesize = $filters['pagesize'] ?? 30;
    $queryParams['limit'] = $pagesize + 1;

    $page = $filters['page'] ?? 1;
    $offset = $pagesize * ($page - 1);
    $queryParams['offset'] = $offset;

    $sql = 
    "
    SELECT 
        j.id, 
        j.project, 
        j.client_id,
        j.priority,
        j.assigned_to,
        j.supervised_by,
        j.reported_by,
        j.reporter_contacts,
        j.description,
        j.`location`,
        j.status,
        j.start_date,
        j.end_date,
        j.completion_notes,
        j.issues_arrising,
        GROUP_CONCAT(DISTINCT 'user_', a.uploaded_by, '/', a.file_name) as files,
        GROUP_CONCAT(DISTINCT t.tag_id) as tags,
        j.created_at
    FROM jc_jobcards as j
    LEFT JOIN jc_attachments as a
    ON j.id = a.jobcard_id
    LEFT JOIN jc_jobcard_tags as t
    ON j.id = t.jobcard_id
    INNER JOIN jc_clients as c
    ON j.client_id = c.id
    LEFT JOIN jc_users as w
    ON j.assigned_to = w.id
    LEFT JOIN jc_users as s
    ON j.supervised_by = s.id
    $filtersPlaceholder
    GROUP BY j.id
    ORDER BY priority DESC, status DESC, created_at $orderBy
    LIMIT :limit
    OFFSET :offset
    ;
    ";
    try {
        
        $stmt = $conn->prepare($sql);
    
        $stmt->execute($queryParams);
    } catch (PDOException $e) {
        throw new Exception('Error executing get jobs query: '.$e->getMessage(), 500);
    }

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $jobs = [];
    if (!count($results)) {
        return ['jobs' => []];
    }
    foreach ($results as $row) {
        if($row['files']) {
            $files = [];
            $paths = explode(',', $row['files']);
            foreach ($paths as $path) {
                $filepath = UPLOAD_PATH . join(DIRECTORY_SEPARATOR, explode('/', $path));
                try {
                    $files[] = getFileInfo($filepath);
                } catch (Exception $e) {
                    if($e->getCode() == 404) {
                        $deleteStmt = $conn->prepare(
                            "DELETE FROM `jc_attachments` 
                            WHERE `jobcard_id` = :job_id 
                            AND `file_name` = :file_name 
                            AND `uploaded_by` = :uploaded_by;"
                        );
                        $deleteStmt->bindParam(':job_id', $row['id'], PDO::PARAM_INT);
                        $deleteStmt->bindValue(':file_name', basename($filepath));
                        $deleteStmt->bindValue(':uploaded_by', extract_user_id($filepath), PDO::PARAM_INT);
                        if(!$deleteStmt->execute()) {
                            // TODO: log error in file
                        }
                        continue;
                    }
                    throw $e;
                }
            }
            $row['files'] = $files;
        }
        if($row['tags']) {
            $row['tags'] = explode(',', $row['tags']);
        }
        $jobs[] = $row;
    }
    if(!(count($results) < $pagesize + 1)) {
        return ["jobs" => $jobs, "has_next_page" => true];
    }
    return ["jobs" => $jobs];
}
function updateJob(mysqli $conn, array $job) {
    $filenames = [];
    try {
        $filenames = uploadFiles();
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
                `reported_by` = ?,
                `reporter_contacts` = ?,
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
            'sissssssssssi', 
            $job['project'], 
            $job['client_id'], 
            $job['priority'], 
            $job['reported_by'],
            $job['reporter_contacts'],
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
    if ($filenames) {
        // IF attachments have been uploaded, post them to the attachments table
        for ($i=0; $i < count($filenames); $i++) { 
            $attachment_Exists = queryRow(
                $conn, 
                'Attachment exists', 
                'SELECT * FROM `jc_attachments` WHERE `jobcard_id` = ? AND `file_name` = ? AND `uploaded_by` = ?;',
                'isi',
                $id,
                $filenames[$i],
                $_SESSION['user_id']
            );
            if($attachment_Exists) {
                continue;
            }
            $sql = 'INSERT INTO jc_attachments (`jobcard_id`, `file_name`, `uploaded_by`) VALUES (?, ?, ?);';
            $stmt = mysqli_stmt_init($conn);
            if(!mysqli_stmt_prepare($stmt, $sql)) {
                throw new Exception("Error preparing sql statement: ". mysqli_stmt_error($stmt), 500);
            }
            $boundOk = mysqli_stmt_bind_param($stmt, 'isi', $id, $filenames[$i], $_SESSION['user_id']);
            if (!$boundOk) {
                throw new Exception("Invalid params: ".mysqli_stmt_error($stmt), 400);
            }
            if(!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error executing insert attachment query: ".mysqli_stmt_error($stmt), 500);
            }
            mysqli_stmt_close($stmt);
        }
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
            j.reported_by,
            j.reporter_contacts,
            j.description,
            j.`location`,
            j.status,
            j.start_date,
            j.end_date,
            j.completion_notes,
            j.issues_arrising,
            GROUP_CONCAT(DISTINCT 'user_', a.uploaded_by, '/', a.file_name) as files,
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
            $files[] = getFileInfo(UPLOAD_PATH . join(DIRECTORY_SEPARATOR, explode('/', $path)));
        }
        $updatedJob['files'] = $files;
    }
    if($updatedJob['tags']) {
        $updatedJob['tags'] =  explode(',', $updatedJob['tags']);
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