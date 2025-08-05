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

if (isset($obj->maintenance_id) && isset($obj->maintenance_code) && isset($obj->maintenance_describtion)) {
    $maintenance_id = $obj->maintenance_id;
    $maintenance_code = $obj->maintenance_code;
    $maintenance_describtion = $obj->maintenance_describtion;
    if (!empty($maintenance_id)) {
        $checkexitsusersql = "UPDATE `maintenance` SET `maintenance_code`='$maintenance_code',`maintenance_describtion`='$maintenance_describtion' WHERE `maintenance_id`='$maintenance_id'";
        $checkexitsuser = mysqli_query($conn, $checkexitsusersql);
        if ($checkexitsuser) {
            $output['status'] = 200;
            $output['msg'] = "Maintenance details updated successfully";
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
