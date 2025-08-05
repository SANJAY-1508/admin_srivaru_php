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

if (isset($obj->model_id)) {
    $model_id = $obj->model_id;
    if (!empty($model_id)) {
        $sql = "UPDATE model SET delete_at = 1 WHERE model_id = '$model_id'";
        if ($conn->query($sql)) {
            $output["status"] = "Success";
            $output["msg"] = "Record deleted successfully";
        } else {
            $output["status"] = "Failed";
            $output["msg"] = "Error updating record: " . $conn->error;
        }

    } else {
        $output["status"] = "Failed";
        $output["msg"] = "Model ID is empty";
    }
} else {
    $output["status"] = "Failed";
    $output["msg"] = "Model ID is not set";
}

echo json_encode($output, JSON_NUMERIC_CHECK);