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

if (isset($obj->grid_fault_id)) {
    $grid_fault_id = $obj->grid_fault_id;
    if (!empty($grid_fault_id)) {
        $sql = "UPDATE grid_fault SET delete_at = 1 WHERE grid_fault_id = '$grid_fault_id'";
        if ($conn->query($sql)) {
            $output["status"] = "Success";
            $output["msg"] = "Record deleted successfully";
        } else {
            $output["status"] = "Failed";
            $output["msg"] = "Error updating record: " . $conn->error;
        }

    } else {
        $output["status"] = "Failed";
        $output["msg"] = "Grid Fault ID is empty";
    }
} else {
    $output["status"] = "Failed";
    $output["msg"] = "Grid Fault ID is not set";
}

echo json_encode($output, JSON_NUMERIC_CHECK);