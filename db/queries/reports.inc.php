<?php
use Monolog\Logger;

require_once('../../utils/constants.php');

function getJobsPerDay(PDO $conn, array $filters, Logger $logger) {
    $timeUnit = $filters['unit'] === 'day' ? 'DATE' : 'MONTH';
    $where = '';
    $params = [];
    if(!isset($filters['all-time'])) {
        if(isset($filters['from']) && !isset($filters['to'])) {
            $where = 'WHERE created_at >= :from';
            $params['from'] = $filters['from'];
        }
        if(!isset($filters['from']) && isset($filters['to'])) {
            $where = 'WHERE created_at <= :to';
            $params['to'] = $filters['to'];
        }
        if (isset($filters['from']) && isset($filters['to'])) {
            $where = 'WHERE created_at BETWEEN :from AND :to';
            $params['from'] = $filters['from'];
            $params['to'] = $filters['to'];
        }
    }
    try {
        $sql = 
            "
            SELECT 
                $timeUnit(created_at) as time_unit, 
                status,
                count(*) as jobs
            FROM jc_jobcards 
            $where
            GROUP BY time_unit, status;
            ";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $report = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $resTimes = null;
        try {
            $conjuntion = strlen($where) ? 'AND' : 'WHERE';
            $sql = 
                "
                SELECT 
                    $timeUnit(created_at) as time_unit,
                    AVG(TIMESTAMPDIFF(HOUR, reported_on, end_date)) as avg_response_time,
                    AVG(TIMESTAMPDIFF(HOUR, start_date, end_date)) as avg_duration
                FROM jc_jobcards 
                $where $conjuntion status = 'COMPLETED'
                GROUP BY time_unit;
                ";
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                $resTimes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $logger->error('Error getting average response times for jobs per day', ['message' => $e->getMessage()]);
        }
        return [
            'report' => $report,
            'response_times' => $resTimes
        ];
    } catch (PDOException $e) {
        $logger->critical('Could not get jobs per day report', ['message' => $e->getMessage()]);
        throw new Error('Could not get jobs per day report: '.$e->getMessage(), 500);
    }
}
function getJobsPerClient(PDO $conn, array $filters, Logger $logger) {
    $where = '';
    $limit = '';
    $params = [];
    if(!isset($filters['all-time'])) {
        if(isset($filters['from']) && !isset($filters['to'])) {
            $where = 'WHERE j.created_at >= :from';
            $params['from'] = $filters['from'];
        }
        if(!isset($filters['from']) && isset($filters['to'])) {
            $where = 'WHERE j.created_at <= :to';
            $params['to'] = $filters['to'];
        }
        if (isset($filters['from']) && isset($filters['to'])) {
            $where = 'WHERE j.created_at BETWEEN :from AND :to';
            $params['from'] = $filters['from'];
            $params['to'] = $filters['to'];
        }
    }
    if(isset($filters['clients']) && strlen(trim($filters['clients']))) {
        $selectedTags = explode(',', $filters['clients']);
        $namedKeys = [];
        for ($i=0; $i < count($selectedTags); $i++) { 
            $key = ':jc_'.$i;
            $namedKeys[] = $key;
            $params[$key] = $selectedTags[$i];
        }
        $clientParams = implode(',', $namedKeys);
        $conjunction = strlen($where) ? 'AND' : 'WHERE';
        $where .= " $conjunction jc.id IN ($clientParams)";

    } else {
        $limit = "ORDER BY jobs DESC LIMIT 20";
    }
    try {
        $sql = 
            "
            SELECT  
                jc.id as client_id, jc.`name` as client,
                count(j.id) as jobs,
                AVG(TIMESTAMPDIFF(HOUR,j.reported_on,j.end_date)) as avg_response_time,
                AVG(TIMESTAMPDIFF(HOUR, j.start_date, j.end_date)) as avg_duration
            FROM jc_jobcards j
            INNER JOIN jc_clients jc ON jc.id = j.client_id
            $where
            GROUP BY jc.id
            $limit
            ;";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $report = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return [
            'report' => $report
        ];
    } catch (PDOException $e) {
        $logger->critical('Could not get jobs per client report', ['message' => $e->getMessage()]);
        throw new Error('Could not get jobs per client report: '.$e->getMessage(), 500);
    }
}
function getJobsPertag(PDO $conn, array $filters, Logger $logger) {
    $where = '';
    $limit = '';
    $params = [];
    if(!isset($filters['all-time'])) {
        if(isset($filters['from']) && !isset($filters['to'])) {
            $where = 'WHERE j.created_at >= :from';
            $params['from'] = $filters['from'];
        }
        if(!isset($filters['from']) && isset($filters['to'])) {
            $where = 'WHERE j.created_at <= :to';
            $params['to'] = $filters['to'];
        }
        if (isset($filters['from']) && isset($filters['to'])) {
            $where = 'WHERE j.created_at BETWEEN :from AND :to';
            $params['from'] = $filters['from'];
            $params['to'] = $filters['to'];
        }
    }
    if(isset($filters['tags']) && strlen(trim($filters['tags']))) {
        $selectedTags = explode(',', $filters['tags']);
        $tagKeys = [];
        for ($i=0; $i < count($selectedTags); $i++) { 
            $key = ':tg_'.$i;
            $tagKeys[] = $key;
            $params[$key] = $selectedTags[$i];
        }
        $tagParams = implode(',', $tagKeys);
        $conjunction = strlen($where) ? 'AND' : 'WHERE';
        $where .= " $conjunction t.id IN ($tagParams)";

    } else {
        $limit = "ORDER BY jobs DESC LIMIT 20";
    }
    try {
        $sql = 
            "
            SELECT 
                t.id, t.`label` as tag, t.colorcode,
                count(j.id) as jobs,
                AVG(TIMESTAMPDIFF(HOUR, j.reported_on, j.end_date)) as avg_response_time,
                AVG(TIMESTAMPDIFF(HOUR, j.start_date, j.end_date)) as avg_duration
            FROM jc_jobcards j
            RIGHT Join jc_jobcard_tags jt ON j.id = jt.jobcard_id
            INNER JOIN jc_tags t ON t.id = jt.tag_id
            $where
            GROUP BY t.id
            $limit
            ;";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $report = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return [
            'report' => $report
        ];
    } catch (PDOException $e) {
        $logger->critical('Could not get jobs per tag report', ['message' => $e->getMessage()]);
        throw new Error('Could not get jobs per tag report: '.$e->getMessage(), 500);
    }
}