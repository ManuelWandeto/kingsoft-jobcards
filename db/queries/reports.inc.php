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
        return [
            'report' => $report
        ];
    } catch (PDOException $e) {
        $logger->critical('Could not get jobs per day report', ['message' => $e->getMessage()]);
        throw new Error('Could not get jobs per day report: '.$e->getMessage(), 500);
    }
}