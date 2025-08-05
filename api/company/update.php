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

if (isset($obj->company_id) && isset($obj->company_name) && isset($obj->mobile_number) && isset($obj->state) && isset($obj->city) && isset($obj->email) && isset($obj->address) && isset($obj->gst_no) && isset($obj->pan_no) && isset($obj->pincode)) {
    $company_id = $obj->company_id;
    $company_name = $obj->company_name;
    $mobile_number = $obj->mobile_number;
    $state = $obj->state;
    $city = $obj->city;
    $pan_no = $obj->pan_no;
    $email = $obj->email;
    $pincode = $obj->pincode;
    $address = $obj->address;
    $gst_no = $obj->gst_no;
    if (!empty($company_id)) {
        $checkexitsusersql = "UPDATE `company` SET `company_name`='$company_name',`mobile_number`='$mobile_number',`state`='$state',`address`='$address',`gst_no`='$gst_no',`email`='$email',`pan_no`='$pan_no',`pincode`='$pincode',`city`='$city' WHERE `company_id`='$company_id'";
        $checkexitsuser = mysqli_query($conn, $checkexitsusersql);
        if ($checkexitsuser) {
            $output['status'] = 200;
            $output['msg'] = "Company details updated successfully";
        } else {
            $output['status'] = 500;
            $output['msg'] = "Error updating Company details: " . mysqli_error($conn);
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
