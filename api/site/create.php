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


if (isset($obj->state_id) && isset($obj->site_name) && isset($obj->short_code)) {
    $state_id = $obj->state_id;
    $site_name = $obj->site_name;
    $short_code = $obj->short_code;


    $checkexitsusersql = "SELECT * FROM `site` WHERE (`site_name` = '$site_name' OR `short_code` = '$short_code')AND `delete_at`='0'";
    $checkexitsuser = mysqli_query($conn, $checkexitsusersql);
    $checkexitsusercount = mysqli_num_rows($checkexitsuser);
    if ($checkexitsusercount > 0) {
        $output['status'] = 400;
        $output['msg'] = "Site details already exists";
        echo "test";
    } else {
        $sql = "INSERT INTO `site`(`state_id`, `site_name`, `short_code`, `delete_at`, `created_date`)
             VALUES ('$state_id','$site_name','$short_code','0','$timestamp')";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $id = $conn->insert_id;
            $unquid_id = uniqueID("site", $id);
            $sql = "UPDATE `site` SET `site_id`='$unquid_id' WHERE `id`='$id'";
            $result = mysqli_query($conn, $sql);
            if ($result) {
                $output['status'] = 200;
                $output['msg'] = "Site Added Successfully";
                $output['data']["site_id"] = $unquid_id;
            } else {
                $output['status'] = 400;
                $output['msg'] = "Site Not Added";
            }
        } else {
            $output['status'] = 400;
            $output['msg'] = "Site Not Added";
        }
    }
} else {
    $output["status"] = 400;
    $output["msg"] = "Parameter Missmatch";
}


echo json_encode($output, JSON_NUMERIC_CHECK);
