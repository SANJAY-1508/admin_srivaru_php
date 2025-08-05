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

if (isset($obj->contract_id) && isset($obj->contract_name) && isset($obj->contract_code)) {
    $contract_id = $obj->contract_id;
    $contract_name = $obj->contract_name;
    $contract_code = $obj->contract_code;
    if (!empty($contract_name) && !empty($contract_code) && !empty($contract_id)) {
        $checkexitsusersql = "UPDATE `contract_type` SET `contract_name`='$contract_name',`contract_code`='$contract_code' WHERE `contract_id`='$contract_id'";
        $checkexitsuser = mysqli_query($conn, $checkexitsusersql);
        if ($checkexitsuser) {
            $output['status'] = 200;
            $output['msg'] = "Contract details updated successfully";
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
