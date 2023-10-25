<?php
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../utils/logger.php');
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 1));
$dotenv->load();
// PDO connection
$pdo_conn = null;

if($_ENV['APP_ENV'] === 'development') {
    $dsn = "mysql:host={$_ENV['DEV_DB_HOST']};dbname={$_ENV['DEV_DB_NAME']};charset=UTF8";
    try {
        $pdo_conn = new PDO($dsn, $_ENV['DEV_DB_USER'], $_ENV['DEV_DB_PASS']);
        $pdo_conn->setAttribute( PDO::ATTR_EMULATE_PREPARES, false );
        $pdo_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if (!$pdo_conn) {
            throw new Exception("Uncaught exception connecting to db", 500);
        }
        // $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        $dbLogger->error('Could not connect to db', ['message'=>$e->getMessage()]);
        respondWith(500, 'PDO:Error connecting to DB: ' . $e->getMessage());
    }
} else {
    $dsn = "mysql:host={$_ENV['PROD_DB_HOST']};dbname={$_ENV['PROD_DB_NAME']};charset=UTF8";
    try {
        $pdo_conn = new PDO($dsn, $_ENV['PROD_DB_USER'], $_ENV['PROD_DB_PASS']);
        $pdo_conn->setAttribute( PDO::ATTR_EMULATE_PREPARES, false );
        $pdo_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if (!$pdo_conn) {
            throw new Exception("Uncaught exception connecting to db", 500);
        }
        
    } catch (PDOException $e) {
        $dbLogger->alert('Could not connect to db', ['message'=>$e->getMessage()]);
        respondWith(500, 'PDO:Error connecting to DB: ' . $e->getMessage());
    }
}