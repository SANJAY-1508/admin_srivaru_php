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

if (isset($obj->error_id) && isset($obj->error_code) && isset($obj->error_describtion)) {
    $error_id = $obj->error_id;
    $error_code = $obj->error_code;
    $error_describtion = $obj->error_describtion;
    if (!empty($error_code) && !empty($error_describtion) && !empty($error_id)) {
        $checkexitsusersql = "UPDATE `error` SET `error_code`='$error_code',`error_describtion`='$error_describtion' WHERE `error_id`='$error_id'";
        $checkexitsuser = mysqli_query($conn, $checkexitsusersql);
        if ($checkexitsuser) {
            $output['status'] = 200;
            $output['msg'] = "Error details updated successfully";
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
