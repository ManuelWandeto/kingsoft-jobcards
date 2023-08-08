<?php
include_once('../utils/respond.php');

// define('DB_HOST', '192.168.100.34');
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_USER', 'imma');
define('DB_PASS', 'imma_');
define('DB_NAME', '2023_2_imma');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if (!$conn) {
    respondWith(500, 'DB connection error');
}