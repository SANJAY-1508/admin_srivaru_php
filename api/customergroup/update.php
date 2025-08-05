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

// Check if required fields are set and not empty
if (isset($obj->customergroup_uniq_id) && isset($obj->customergroup_name) && isset($obj->customergroup_id) && isset($obj->customeruser_name) && isset($obj->customer_password)) {
    $customergroup_uniq_id = $obj->customergroup_uniq_id;
    $customergroup_name = $obj->customergroup_name;
    $customergroup_id = $obj->customergroup_id;
    $customeruser_name = $obj->customeruser_name;
    $customer_password = $obj->customer_password;

    // Validate that none of the fields are empty
    if (!empty($customergroup_name) && !empty($customergroup_id) && !empty($customergroup_uniq_id) && !empty($customeruser_name) && !empty($customer_password)) {
        $checkexitsusersql = "UPDATE `customer_group` SET `customergroup_name`='$customergroup_name', `customergroup_id`='$customergroup_id', `customeruser_name`='$customeruser_name', `customer_password`='$customer_password' WHERE `customergroup_uniq_id`='$customergroup_uniq_id'";
        $checkexitsuser = mysqli_query($conn, $checkexitsusersql);
        if ($checkexitsuser) {
            $output['status'] = 200;
            $output['msg'] = "Customer Group updated successfully";
        } else {
            $output['status'] = 500;
            $output['msg'] = "Error updating customer group: " . mysqli_error($conn);
        }
    } else {
        $output["status"] = 400;
        $output["msg"] = "One or more parameters are empty";
    }
} else {
    $output["status"] = 400;
    $output["msg"] = "Parameter mismatch or missing";
}

echo json_encode($output, JSON_NUMERIC_CHECK);