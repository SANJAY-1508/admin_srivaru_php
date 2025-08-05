<?php

include ("./config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$output = array();
date_default_timezone_set('Asia/Calcutta');
$timestamp = date('Y-m-d');
$data = json_decode(file_get_contents("php://input"));

if (isset($data->name) && isset($data->email) && isset($data->message)) {
    $msgName = $data->name;
    $msgEmail = $data->email;
    $msgMessage = $data->message;


    $msgName = filter_var($msgName ?? '', FILTER_SANITIZE_STRING);
    $msgEmail = filter_var($msgEmail ?? '', FILTER_SANITIZE_EMAIL);
    $msgMessage = filter_var($msgMessage ?? '', FILTER_SANITIZE_STRING);

    if (!filter_var($msgEmail, FILTER_VALIDATE_EMAIL)) {
        $output["status"] = 400;
        $output["msg"] = "Invalid email format";
        $output["data"] = [];
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit;
    }else{

        $to = "info@srivarugreenenergy.com"; // Change this to your email
        $subject = "New Contact Form Submission";
        $headers = "From: info@srivarugreenenergy.com\r\n" .
                "Reply-To: info@srivarugreenenergy.com\r\n" .
                "Content-Type: text/plain; charset=UTF-8\r\n";
        
        $body = "Name: $msgName\n" .
                "Email: $msgEmail\n" .
                "Message:\n$msgMessage";
        
        if (mail($to, $subject, $body, $headers)) {
            $output["status"] = 200;
            $output["msg"] = "Thank you for your message. We will get back to you soon.";
            $output["data"] = [];
            echo json_encode($output, JSON_NUMERIC_CHECK);
            exit;
        } else {
            $output["status"] = 400;
            $output["msg"] = "Error sending message. Please try again later.";
            $output["data"] = [];
            echo json_encode($output, JSON_NUMERIC_CHECK);
            exit;
        }
    }
}else{
    $output["status"] = 400;
    $output["msg"] = "Invalid request method.";
    $output["data"] = [];
    echo json_encode($output, JSON_NUMERIC_CHECK);
    exit;
}