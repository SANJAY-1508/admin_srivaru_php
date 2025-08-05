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


if (isset($obj->state_id) && isset($obj->site_id) && isset($obj->location_name)) {
    $state_id = $obj->state_id;
    $site_id = $obj->site_id;
    $location_name = $obj->location_name;

    if (!empty($state_id) && !empty($site_id) && !empty($location_name)) {
        $checkexitsusersql = "SELECT * FROM `location` WHERE `location_name` = '$location_name' AND `delete_at`='0'";
        $checkexitsuser = mysqli_query($conn, $checkexitsusersql);
        $checkexitsusercount = mysqli_num_rows($checkexitsuser);
        if ($checkexitsusercount > 0) {
            $output['status'] = 400;
            $output['msg'] = "Location details already exists";
        } else {
            $sql = "INSERT INTO `location`(`state_id`, `site_id`, `location_name`, `delete_at`, `created_date`)
             VALUES ('$state_id','$site_id','$location_name','0','$timestamp')";
            $result = mysqli_query($conn, $sql);
            if ($result) {
                $id = $conn->insert_id;
                $unquid_id = uniqueID("Location", $id);
                $sql = "UPDATE `location` SET `location_id`='$unquid_id' WHERE `id`='$id'";
                $result = mysqli_query($conn, $sql);
                if ($result) {
                    $output['status'] = 200;
                    $output['msg'] = "Location Added Successfully";
                    $output['data']["location_id"] = $unquid_id;
                } else {
                    $output['status'] = 400;
                    $output['msg'] = "Location Not Added";
                }
            } else {
                $output['status'] = 400;
                $output['msg'] = "Location Not Added";
            }
        }
    } else {
        $output["status"] = 400;
        $output["msg"] = "Some Parameter is Empty";
    }
} else {
    $output["status"] = 400;
    $output["msg"] = "Parameter is Mismatch";
}

echo json_encode($output, JSON_NUMERIC_CHECK);
