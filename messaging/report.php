<?php
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../utils/constants.php');
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 1));
$dotenv->load();

use Twilio\Rest\Client;
function sendWhatsappMessage(string $content, string $to = '', string $from = '') {
    $to = $to ?? $_ENV['REPORT_RECEIVER'];
    $from = $from ?? $_ENV['APP_PHONE'];

    try {
        $client = new Client($_ENV['TWILIO_SID'], $_ENV['TWILIO_TOKEN']);
        $message = $client->messages->create(
            "whatsapp:$to",
            array(
                "from"=>"whatsapp:$from",
                "body"=>$content
            ));
        
        if($message->errorMessage) {
            throw new Exception("Error occured sending message: {$message->errorMessage}", 500);
        }
        return $message->errorMessage;
    } catch (Exception $e) {
        // TODO: Log reporting errors to a log file
        throw new Exception($e->getMessage(), 500);
    }
};