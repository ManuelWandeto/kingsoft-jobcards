<?php
use Monolog\Logger;
require_once(__DIR__ . '/../functions.inc.php');
require_once('tags.inc.php');
require_once('files.inc.php');
function addJob(PDO $conn, array $jobData, Logger $logger) {
    $filenames = [];
    try {
        $filenames = uploadFiles();
    } catch (Exception $e) {
        $logger->withName('uploads')->error('Error uploading attachments', ['message'=>$e->getMessage()]);
        throw $e;
    }
    try {
        //code...
        $sql = "INSERT INTO jc_jobcards 
                    (
                        `project`, 
                        client_id, 
                        `priority`,  
                        `assigned_to`, 
                        `supervised_by`, 
                        `reported_on`,
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
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $jobData['project'], 
            $jobData['client_id'], 
            $jobData['priority'], 
            isset($jobData['assigned_to']) ? $jobData['assigned_to'] : null, 
            isset($jobData['supervised_by']) ? $jobData['supervised_by'] : null, 
            $jobData['reported_on'], 
            $jobData['reported_by'], 
            $jobData['reporter_contacts'], 
            $jobData['description'], 
            $jobData['location'], 
            $jobData['status'], 
            $jobData['start_date'], 
            $jobData['end_date'],
            $jobData['completion_notes'],
            $jobData['issues_arrising'],
        ]);
        $lastInsertId = $conn->lastInsertId();
        if ($filenames) {
            // IF attachments have been uploaded, post them to the attachments table
            $sql = 'INSERT INTO jc_attachments (`jobcard_id`, `file_name`, `uploaded_by`) VALUES (?, ?, ?)';
            $stmt = $conn->prepare($sql);
            for ($i=0; $i < count($filenames); $i++) { 
                $stmt->execute([
                    $lastInsertId, $filenames[$i], $_SESSION['user_id']
                ]);
            }
        }
        $tags = $jobData['tags'][0] ? array_map('intval', explode(',', $jobData['tags'][0])) : [];
    
        try {
            //code...
            if ($tags) {
                $sql = 'INSERT INTO jc_jobcard_tags (`jobcard_id`, `tag_id`) VALUES (?, ?)';
                $stmt = $conn->prepare($sql);
                for ($i=0; $i < count($tags); $i++) { 
                    $stmt->execute([$lastInsertId, $tags[$i]]);
                }
            }
        } catch (PDOException $e) {
            $logger->error('Error inserting jobcard tags while adding job', ['message'=>$e->getMessage()]);
        }
        $getNewJob = "
            SELECT 
                j.id, 
                j.project, 
                j.client_id,
                j.priority,
                j.assigned_to,
                j.supervised_by,
                j.reported_on,
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
        $newJob = queryRowPdo($conn, 'get inserted job', $getNewJob, $lastInsertId); 
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

    } catch (Exception $e) {
        $logger->error('Error adding job', ['message'=>$e->getMessage()]);
        throw new Exception('Error adding job: '.$e->getMessage(), 500);
    }
}
function getJobs(PDO $conn, array $filters, Logger $logger) {
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
        j.reported_on,
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
        $logger->critical('Error getting jobs', ['message'=>$e->getMessage()]);
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
                            $logger->error('Error deleting attachment record of missing file', ['message'=>$e->getMessage()]);
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
        array_pop($jobs);
        return ["jobs" => $jobs, "has_next_page" => true];
    }
    return ["jobs" => $jobs];
}
function updateJob(PDO $conn, array $job, Logger $logger) {
    $filenames = [];
    try {
        $filenames = uploadFiles();
    } catch (Exception $e) {
        $logger->error('Error uploading jobcard updated attachments', ['message'=>$e->getMessage()]);
        throw $e;
    }

    try {
        //code...
        $id = $job['id'];
        $conn->beginTransaction();
        // check if row with given id exists
        if (!IdExistsPdo($conn, $id, 'jc_jobcards')) {
            throw new Exception("Job record with id: $id not found", 404);
        }
        $sql = "UPDATE jc_jobcards 
                SET     
                    `project` = ?, 
                    client_id = ?, 
                    `priority` = ?,  
                    `assigned_to` = ?, 
                    `supervised_by` = ?, 
                    `reported_on` = ?,
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
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $job['project'], 
            $job['client_id'], 
            $job['priority'], 
            intval($job['assigned_to']) ? $job['assigned_to'] : null,
            intval($job['supervised_by']) ? $job['supervised_by'] : null,
            $job['reported_on'],
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
    
        ]);
        if ($filenames) {
            // IF attachments have been uploaded, post them to the attachments table
            try {
                //code...
                for ($i=0; $i < count($filenames); $i++) { 
                    $attachment_Exists = queryRowPdo(
                        $conn, 
                        'Attachment exists', 
                        'SELECT * FROM `jc_attachments` WHERE `jobcard_id` = ? AND `file_name` = ? AND `uploaded_by` = ?;',
                        $id,
                        $filenames[$i],
                        $_SESSION['user_id']
                    );
                    if($attachment_Exists) {
                        continue;
                    }
                    $sql = 'INSERT INTO jc_attachments (`jobcard_id`, `file_name`, `uploaded_by`) VALUES (?, ?, ?);';
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$id, $filenames[$i], $_SESSION['user_id']]);
                }
            } catch (PDOException $e) {
                $logger->error('Error posting attachments to db', ['message'=>$e->getMessage()]);
            }
        }
        $oldTagsRecord = queryRowPdo(
            $conn, 
            'get tags for jobcard', 
            "SELECT GROUP_CONCAT(t.tag_id) as tags FROM jc_jobcard_tags t WHERE  t.jobcard_id = ?;", 
            $id
        );
    
        $oldTags = $oldTagsRecord['tags'] ? array_map('intval', explode(',', $oldTagsRecord['tags'])) : [];
    
        $newTags = $job['tags'][0] ? array_map('intval', explode(',', $job['tags'][0])) : [];
        // Get old tags not included in the new and remove them
        $removedTags = array_values(array_diff($oldTags, $newTags));
    
        if($removedTags) {
            try {
                //code...
                $sql = 'DELETE FROM jc_jobcard_tags WHERE jobcard_id = ? AND tag_id = ?;';
                $stmt = $conn->prepare($sql);
                for ($i=0; $i < count($removedTags); $i++) { 
                    $stmt->execute([$id, $removedTags[$i]]);
                }
            } catch (PDOException $e) {
                $logger->error('Error in update jobcard query while removing old tags', ['message'=>$e->getMessage()]);
            }
        }
        // get new tags not already in the old and add them
        $tags = array_values(array_diff($newTags, $oldTags));
        
        if ($tags) {
            try {
                $sql = 'INSERT INTO jc_jobcard_tags (`jobcard_id`, `tag_id`) VALUES (?, ?);';
                $stmt = $conn->prepare($sql);
                for ($i=0; $i < count($tags); $i++) { 
                    $stmt->execute([$id, $tags[$i]]);
                }
            } catch (Exception $e) {
                $logger->error('Error in update jobcard query while posting new tags', ['message'=>$e->getMessage()]);
            }
        }
        $conn->commit();

        try {
            //code...
            $getUpdatedJob = "
                SELECT 
                    j.id, 
                    j.project, 
                    j.client_id,
                    j.priority,
                    j.assigned_to,
                    j.supervised_by,
                    j.reported_on,
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
            $updatedJob = queryRowPdo($conn, 'get inserted job', $getUpdatedJob, $id); 
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
        } catch (Exception $e) {
            $logger->error('Error in update jobcard query while getting new job', ['message'=>$e->getMessage()]);
        }

    } catch (Exception $e) {
        $conn->rollBack();
        $logger->error('Error updating job', ['message'=>$e->getMessage()]);
        throw new Error('Error updating job: '.$e->getMessage(), 500);
    }
}
function finaliseJob(PDO $conn, array $job, Logger $logger) {
    $id = $job['id'];
    try {
        // check if row with given id exists
        if (!IdExistsPdo($conn, $id, 'jc_jobcards')) {
            throw new Exception("Job record with id: $id not found", 404);
        }
        // if id exists, perform update
        $sql = 'UPDATE jc_jobcards 
                SET     
                    `status` = ?, 
                    completion_notes = ?, 
                    issues_arrising = ?
                WHERE id = ?;';
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $job['status'], 
            $job['completion_notes'],
            $job['issues_arrising'],
            $id
        ]);
        $getUpdatedJob = "
        SELECT 
            j.id, 
            j.project, 
            j.client_id,
            j.priority,
            j.assigned_to,
            j.supervised_by,
            j.reported_on,
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
        $job = queryRowPdo($conn, 'Get updated job', $getUpdatedJob, $id);
        return $job;
    } catch (PDOException $e) {
        $logger->error('Error finalizing job', ['message'=>$e->getMessage()]);
        throw new Error('Error finalizing job: '.$e->getMessage(), 500);
    }
}