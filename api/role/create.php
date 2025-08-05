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


if (isset($obj->role_name)) {
    $role_name = $obj->role_name;

    if (!empty($role_name)) {
        $checkexitsusersql = "SELECT * FROM `role` WHERE `role_name` = '$role_name' AND `delete_at`='0'";
        $checkexitsuser = mysqli_query($conn, $checkexitsusersql);
        $checkexitsusercount = mysqli_num_rows($checkexitsuser);
        if ($checkexitsusercount > 0) {
            $output['status'] = 400;
            $output['msg'] = "Role details already exists";
        } else {
            $sql = "INSERT INTO `role`(`role_name`, `delete_at`, `created_date`)
             VALUES ('$role_name','0','$timestamp')";
            $result = mysqli_query($conn, $sql);
            if ($result) {
                $id = $conn->insert_id;
                $unquid_id = uniqueID("role", $id);
                $sql = "UPDATE `role` SET `role_id`='$unquid_id' WHERE `id`='$id'";
                $result = mysqli_query($conn, $sql);
                if ($result) {
                    $output['status'] = 200;
                    $output['msg'] = "Role Added Successfully";
                    $output["data"]["role_id"] = $unquid_id;
                } else {
                    $output['status'] = 400;
                    $output['msg'] = "Role Not Added";
                }
            } else {
                $output['status'] = 400;
                $output['msg'] = "Role Not Added";
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
