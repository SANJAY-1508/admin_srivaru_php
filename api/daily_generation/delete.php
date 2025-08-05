<?php

include ("../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");

header('Content-Type: application/json; charset=utf-8');

$json = file_get_contents('php://input');
$obj = json_decode($json);
$output = array();

date_default_timezone_set('Asia/Calcutta');
$timestamp = date('Y-m-d h:i:s');

if (isset($obj->daily_generation_id)) {
    $daily_generation_id = $obj->daily_generation_id;
    if (!empty($daily_generation_id)) {
        $sql = "UPDATE daily_generation SET delete_at = 1 WHERE daily_generation_id = '$daily_generation_id'";
        if ($conn->query($sql)) {
            $output["status"] = 200;
            $output["msg"] = "Record deleted successfully";
        } else {
            $output["status"] = 400;
            $output["msg"] = "Daily Generation updating record: " . $conn->error;
        }

    } else {
        $output["status"] = 400;
        $output["msg"] = "Daily Generation ID is empty";
    }
} else {
    $output["status"] = 400;
    $output["msg"] = "Daily Generation ID is not set";
}

echo json_encode($output, JSON_NUMERIC_CHECK);