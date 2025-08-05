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

if (isset($obj->contract_id)) {
    $contract_id = $obj->contract_id;
    if (!empty($contract_id)) {
        $sql = "UPDATE contract_type SET delete_at = 1 WHERE contract_id = '$contract_id'";
        if ($conn->query($sql)) {
            $output["status"] = "Success";
            $output["msg"] = "Record deleted successfully";
        } else {
            $output["status"] = "Failed";
            $output["msg"] = "Contract Type updating record: " . $conn->error;
        }

    } else {
        $output["status"] = "Failed";
        $output["msg"] = "Contract Type ID is empty";
    }
} else {
    $output["status"] = "Failed";
    $output["msg"] = "Contract Type ID is not set";
}

echo json_encode($output, JSON_NUMERIC_CHECK);