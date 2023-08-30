<?php
require_once(__DIR__ . '/../vendor/autoload.php');

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 1));
$dotenv->load();
$conn = null;

if($_ENV['APP_ENV'] === 'development') {
    $conn = mysqli_connect(
        $_ENV['DEV_DB_HOST'], 
        $_ENV['DEV_DB_USER'], 
        $_ENV['DEV_DB_PASS'], 
        $_ENV['DEV_DB_NAME'], 
        $_ENV['DEV_DB_PORT']
    );
} else {
    $conn = mysqli_connect(
        $_ENV['PROD_DB_HOST'], 
        $_ENV['PROD_DB_USER'], 
        $_ENV['PROD_DB_PASS'], 
        $_ENV['PROD_DB_NAME'], 
        $_ENV['PROD_DB_PORT']
    );
}

if (!$conn) {
    respondWith(500, 'DB connection error');
}