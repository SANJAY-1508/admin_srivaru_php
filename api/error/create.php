<?php

include("../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");

header('Content-Type: application/json; charset=utf-8');

$json = file_get_contents('php://input');
$obj = json_decode($json);
$output = array();


date_default_timezone_set('Asia/Calcutta');
$timestamp = date('Y-m-d h:i:s');


if (isset($obj->error_code) && isset($obj->error_describtion)) {
    $error_code = $obj->error_code;
    $error_describtion = $obj->error_describtion;

    if (!empty($error_code)) {
        $checkexitsusersql = "SELECT * FROM `error` WHERE `error_code` = '$error_code' AND `delete_at`='0'";
        $checkexitsuser = mysqli_query($conn, $checkexitsusersql);
        $checkexitsusercount = mysqli_num_rows($checkexitsuser);
        if ($checkexitsusercount > 0) {
            $output['status'] = 400;
            $output['msg'] = "Error details already exists";
        } else {
            $sql = "INSERT INTO `error`(`error_code`,`error_describtion`, `delete_at`, `created_date`)
             VALUES ('$error_code','$error_describtion','0','$timestamp')";
            $result = mysqli_query($conn, $sql);
            if ($result) {
                $id = $conn->insert_id;
                $unquid_id  = uniqueID("error", $id);
                $sql = "UPDATE `error` SET `error_id`='$unquid_id' WHERE `id`='$id'";
                $result = mysqli_query($conn, $sql);
                if ($result) {
                    $output['status'] = 200;
                    $output['msg'] = "Error Added Successfully";
                } else {
                    $output['status'] = 400;
                    $output['msg'] = "Error Not Added";
                }
            } else {
                $output['status'] = 400;
                $output['msg'] = "Error Not Added";
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
