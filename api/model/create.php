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


if (isset($obj->model_type)) {
    $model_type = $obj->model_type;

    if (!empty($model_type)) {
        $checkexitsusersql = "SELECT * FROM `model` WHERE `model_type` = '$model_type'AND `delete_at`='0'";
        $checkexitsuser = mysqli_query($conn, $checkexitsusersql);
        $checkexitsusercount = mysqli_num_rows($checkexitsuser);
        if ($checkexitsusercount > 0) {
            $output['status'] = 400;
            $output['msg'] = "Model details already exists";
        } else {
            $sql = "INSERT INTO `model`(`model_type`, `delete_at`, `created_date`)
             VALUES ('$model_type','0','$timestamp')";
            $result = mysqli_query($conn, $sql);
            if ($result) {
                $id = $conn->insert_id;
                $unquid_id = uniqueID("Model", $id);
                $sql = "UPDATE `model` SET `model_id`='$unquid_id' WHERE `id`='$id'";
                $result = mysqli_query($conn, $sql);
                if ($result) {
                    $output['status'] = 200;
                    $output['msg'] = "Model Added Successfully";
                    $output['data']["model_id"] = $unquid_id;
                } else {
                    $output['status'] = 400;
                    $output['msg'] = "Model Not Added";
                }
            } else {
                $output['status'] = 400;
                $output['msg'] = "Model Not Added";
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
