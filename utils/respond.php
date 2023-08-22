<?php
require_once(__DIR__ ."/../vendor/autoload.php");
use GuzzleHttp\Psr7\Response;


function respondWith(int $statusCode, string $message) {
    $res = new Response($statusCode);
    header('HTTP/ ' . $statusCode. ' '. $res->getReasonPhrase(), $statusCode);
    echo json_encode($message);
    exit();
}