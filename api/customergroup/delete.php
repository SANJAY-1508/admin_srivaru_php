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

if (isset($obj->customergroup_uniq_id)) {
    $customergroup_uniq_id = $obj->customergroup_uniq_id;
    if (!empty($customergroup_uniq_id)) {
        $sql = "UPDATE customer_group SET delete_at = 1 WHERE customergroup_uniq_id = '$customergroup_uniq_id'";
        if ($conn->query($sql)) {
            $output["status"] = "Success";
            $output["msg"] = "Record deleted successfully";
        } else {
            $output["status"] = "Failed";
            $output["msg"] = "Customer Group updating record: " . $conn->error;
        }

    } else {
        $output["status"] = "Failed";
        $output["msg"] = "Customer Group ID is empty";
    }
} else {
    $output["status"] = "Failed";
    $output["msg"] = "Customer Group ID is not set";
}

echo json_encode($output, JSON_NUMERIC_CHECK);