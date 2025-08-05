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


if (isset($obj->maintenance_code) && isset($obj->maintenance_describtion)) {
    $maintenance_code = $obj->maintenance_code;
    $maintenance_describtion = $obj->maintenance_describtion;
    $checkexitsusersql = "SELECT * FROM `maintenance` WHERE `maintenance_code` = '$maintenance_code' AND `delete_at`='0'";
    $checkexitsuser = mysqli_query($conn, $checkexitsusersql);
    $checkexitsusercount = mysqli_num_rows($checkexitsuser);
    if ($checkexitsusercount > 0) {
        $output['status'] = 400;
        $output['msg'] = "Maintenance details already exists";
    } else {
        $sql = "INSERT INTO `maintenance`(`maintenance_code`,`maintenance_describtion`, `delete_at`, `created_date`)
             VALUES ('$maintenance_code','$maintenance_describtion','0','$timestamp')";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $id = $conn->insert_id;
            $unquid_id = uniqueID("maintenance", $id);
            $sql = "UPDATE `maintenance` SET `maintenance_id`='$unquid_id' WHERE `id`='$id'";
            $result = mysqli_query($conn, $sql);
            if ($result) {
                $output['status'] = 200;
                $output['msg'] = "Maintenance Added Successfully";
            } else {
                $output['status'] = 400;
                $output['msg'] = "Maintenance Not Added";
            }
        } else {
            $output['status'] = 400;
            $output['msg'] = "Maintenance Not Added";
        }
    }

} else {
    $output["status"] = 400;
    $output["msg"] = "Parameter is Mismatch";
}
echo json_encode($output, JSON_NUMERIC_CHECK);
