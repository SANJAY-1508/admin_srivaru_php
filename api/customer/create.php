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


if (isset($obj->customer_name) && isset($obj->customer_id) && isset($obj->pan_no) && isset($obj->gst_no) && isset($obj->customer_user_id) && isset($obj->password) && isset($obj->street) && isset($obj->city) && isset($obj->state) && isset($obj->postal_code) && isset($obj->country) && isset($obj->contact) && isset($obj->ship_address) && isset($obj->customergroupname_id)) {
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
    $contact = $obj->contact;
    $ship_address = $obj->ship_address;
    $customergroupname_id = $obj->customergroupname_id;

    // Check if the contact parameter is an array
    if (!is_array($contact)) {
        $output["status"] = 400;
        $output["msg"] = "Contact parameter must be an array";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit;
    }

    // Check if the ship_addresses parameter is an array
    if (!is_array($ship_address)) {
        $output["status"] = 400;
        $output["msg"] = "Ship addresses parameter must be an array";
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit;
    }



    // Loop through each contact object and encode it to JSON
    foreach ($contact as $eachcontact) {
        if (!isset($eachcontact->contact_person) || !isset($eachcontact->job_role) || !isset($eachcontact->phone_no) || !isset($eachcontact->email)) {
            $output["status"] = 400;
            $output["msg"] = "Invalid contact details";
            echo json_encode($output, JSON_NUMERIC_CHECK);
            exit;
        }
    }

    // Loop through each shipping address object and encode it to JSON
    foreach ($ship_address as $each_ship_address) {
        if (!isset($each_ship_address->ship_street) || !isset($each_ship_address->ship_city) || !isset($each_ship_address->ship_state) || !isset($each_ship_address->ship_pincode) || !isset($each_ship_address->ship_country)) {
            $output["status"] = 400;
            $output["msg"] = "Invalid shipping address details";
            echo json_encode($output, JSON_NUMERIC_CHECK);
            exit;
        }
    }
    $contact = json_encode($contact);
    $ship_address = json_encode($ship_address);

    if (!empty($customer_name) && !empty($customer_id) && !empty($pan_no)) {
        $checkexitsusersql = "SELECT * FROM `customer` WHERE (`pan_no` = '$pan_no' OR `gst_no` = '$gst_no') AND `delete_at`='0'";
        $checkexitsuser = mysqli_query($conn, $checkexitsusersql);
        $checkexitsusercount = mysqli_num_rows($checkexitsuser);
        if ($checkexitsusercount > 0) {
            $output['status'] = 400;
            $output['msg'] = "Customer details already exist";
        } else {
            $sql = "INSERT INTO `customer`(`customer_name`, `customer_id`, `pan_no`, `gst_no`, `customer_user_id`, `password`,`street`,`state`,`city`,`postal_code`,`country`, `contact`, `ship_address`,`customergroupname_id`, `delete_at`, `created_date`)
             VALUES ('$customer_name','$customer_id','$pan_no','$gst_no','$customer_user_id','$password','$street','$state','$city','$postal_code','$country','$contact','$ship_address','$customergroupname_id','0','$timestamp')";
            $result = mysqli_query($conn, $sql);
            if ($result) {
                $id = $conn->insert_id;
                $unquid_id = uniqueID("Customer", $id);
                $sql = "UPDATE `customer` SET `customer_unique_id`='$unquid_id' WHERE `id`='$id'";
                $result = mysqli_query($conn, $sql);
                if ($result) {
                    $output['status'] = 200;
                    $output['msg'] = "Customer Added Successfully";
                } else {
                    $output['status'] = 400;
                    $output['msg'] = "Customer Not Added";
                }
            } else {
                $output['status'] = 400;
                $output['msg'] = "Customer Not Added";
            }
        }
    } else {
        $output["status"] = 400;
        $output["msg"] = "Some Parameter is Empty";
    }
} else {
    $output["status"] = 400;
    $output["msg"] = "Parameters are Mismatched";
}

echo json_encode($output, JSON_NUMERIC_CHECK);
