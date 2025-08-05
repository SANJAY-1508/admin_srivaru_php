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

if (isset($obj->role_id) && isset($obj->role_name)) {
    $role_id = $obj->role_id;
    $role_name = $obj->role_name;
    if (!empty($role_id)) {
        $checkexitsusersql = "UPDATE `role` SET `role_name`='$role_name' WHERE `role_id`='$role_id'";
        $checkexitsuser = mysqli_query($conn, $checkexitsusersql);
        if ($checkexitsuser) {
            $output['status'] = 200;
            $output['msg'] = "Role details updated successfully";
        } else {
            $output['status'] = 500;
            $output['msg'] = "Role updating user details: " . mysqli_error($conn);
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
