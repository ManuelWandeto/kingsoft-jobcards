<?php
session_start();
enum Severity {
    case TRACE;
    case DEBUG;
    case INFO;
    case WARN;
    case ERROR;
    case FATAL;
}
// Should log errors to db jc_logs table, if that fails, should log to file
function jobcardsLog(
    mysqli $conn,
    Severity $severity, 
    string $actionType, 
    string $description, 
    string $stackTrace = null,
    int $user_id = null
) {
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }

}