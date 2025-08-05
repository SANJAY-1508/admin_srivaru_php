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

if (isset($obj->grid_fault_id) && isset($obj->grid_fault_code) && isset($obj->grid_fault_describtion)) {
    $grid_fault_id = $obj->grid_fault_id;
    $grid_fault_code = $obj->grid_fault_code;
    $grid_fault_describtion = $obj->grid_fault_describtion;
    if (!empty($grid_fault_code) && !empty($grid_fault_describtion) && !empty($grid_fault_id)) {
        $checkexitsusersql = "UPDATE `grid_fault` SET `grid_fault_code`='$grid_fault_code',`grid_fault_describtion`='$grid_fault_describtion' WHERE `grid_fault_id`='$grid_fault_id'";
        $checkexitsuser = mysqli_query($conn, $checkexitsusersql);
        if ($checkexitsuser) {
            $output['status'] = 200;
            $output['msg'] = "Grid fault details updated successfully";
        } else {
            $output['status'] = 500;
            $output['msg'] = "Error updating user details: " . mysqli_error($conn);
        }
    } else {
        $output["status"] = 400;
        $output["msg"] = "Some Parameter is Empty";
    }
} else {
    $output["status"] = 400;
    $output["msg"] = "Parameter mismatch";
}

echo json_encode($output, JSON_NUMERIC_CHECK);
