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

if (isset($obj->location_id) && isset($obj->state_id) && isset($obj->site_id) && isset($obj->location_name)) {
    $location_id = $obj->location_id;
    $state_id = $obj->state_id;
    $site_id = $obj->site_id;
    $location_name = $obj->location_name;
    if (!empty($location_id)) {
        $checkexitsusersql = "UPDATE `location` SET `state_id`='$state_id',`site_id`='$site_id',`location_name`='$location_name' WHERE `location_id`='$location_id'";
        $checkexitsuser = mysqli_query($conn, $checkexitsusersql);
        if ($checkexitsuser) {
            $output['status'] = 200;
            $output['msg'] = "Location details updated successfully";
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
