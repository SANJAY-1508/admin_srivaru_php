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

if (isset($obj->site_id) && isset($obj->state_id) && isset($obj->site_name) && isset($obj->short_code)) {
    $site_id = $obj->site_id;
    $state_id = $obj->state_id;
    $site_name = $obj->site_name;
    $short_code = $obj->short_code;
    if (!empty($site_id)) {
        $checkexitsusersql = "UPDATE `site` SET `state_id`='$state_id',`site_name`='$site_name',`short_code`='$short_code' WHERE `site_id`='$site_id'";
        $checkexitsuser = mysqli_query($conn, $checkexitsusersql);
        if ($checkexitsuser) {
            $output['status'] = 200;
            $output['msg'] = "Site details updated successfully";
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
