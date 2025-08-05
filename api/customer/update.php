<?php

include ("../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$json = file_get_contents('php://input');
$obj = json_decode($json);
$output = array();

date_default_timezone_set('Asia/Calcutta');
$timestamp = date('Y-m-d');

if (isset($obj->customer_unique_id) && isset($obj->customer_name) && isset($obj->customer_id) && isset($obj->pan_no) && isset($obj->gst_no) && isset($obj->customer_user_id) && isset($obj->password) && isset($obj->street) && isset($obj->city) && isset($obj->state) && isset($obj->postal_code) && isset($obj->country) && isset($obj->contact) && isset($obj->ship_address) && isset($obj->customergroupname_id)) {
    $customer_unique_id = $obj->customer_unique_id;
    $customer_name = $obj->customer_name;
    $customer_id = $obj->customer_id;
    $pan_no = $obj->pan_no;
    $gst_no = $obj->gst_no;
    $customer_user_id = $obj->customer_user_id;
    $password = $obj->password;
    $street = $obj->street;
    $city = $obj->city;
    $state = $obj->state;
    $postal_code = $obj->postal_code;
    $country = $obj->country;
    $contact = json_encode($obj->contact);
    $ship_address = json_encode($obj->ship_address);
    $customergroupname_id = $obj->customergroupname_id; 
    if (!empty($customer_unique_id)) {
        $checkexitsusersql = "UPDATE `customer` SET `customer_name`='$customer_name',`customer_id`='$customer_id',`pan_no`='$pan_no',`gst_no`='$gst_no',`customer_user_id`='$customer_user_id',`password`='$password',`street`='$street',`city`='$city',`state`='$state',`postal_code`='$postal_code',`country`='$country',`contact`='$contact',`ship_address`='$ship_address',`customergroupname_id`='$customergroupname_id' WHERE `customer_unique_id`='$customer_unique_id'";
        $checkexitsuser = mysqli_query($conn, $checkexitsusersql);
        if ($checkexitsuser) {
            $output['status'] = 200;
            $output['msg'] = "Customer details updated successfully";
        } else {
            $output['status'] = 500;
            $output['msg'] = "Customer updating user details: " . mysqli_error($conn);
        }
    } else {
        $output["status"] = 400;
        $output["msg"] = "Customer Parameter is Empty";
    }
} else {
    $output["status"] = 400;
    $output["msg"] = "Parameter mismatch";
}

echo json_encode($output, JSON_NUMERIC_CHECK);