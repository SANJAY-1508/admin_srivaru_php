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


if (isset($obj->grid_fault_code) && isset($obj->grid_fault_describtion)) {
    $grid_fault_code = $obj->grid_fault_code;
    $grid_fault_describtion = $obj->grid_fault_describtion;

    if (!empty($grid_fault_code)) {
        $checkexitsusersql = "SELECT * FROM `grid_fault` WHERE `grid_fault_code` = '$grid_fault_code' AND `delete_at`='0'";
        $checkexitsuser = mysqli_query($conn, $checkexitsusersql);
        $checkexitsusercount = mysqli_num_rows($checkexitsuser);
        if ($checkexitsusercount > 0) {
            $output['status'] = 400;
            $output['msg'] = "Grid Fault details already exists";
        } else {
            $sql = "INSERT INTO `grid_fault`(`grid_fault_code`,`grid_fault_describtion`, `delete_at`, `created_date`)
             VALUES ('$grid_fault_code','$grid_fault_describtion','0','$timestamp')";
            $result = mysqli_query($conn, $sql);
            if ($result) {
                $id = $conn->insert_id;
                $unquid_id  = uniqueID("grid_fault", $id);
                $sql = "UPDATE `grid_fault` SET `grid_fault_id`='$unquid_id' WHERE `id`='$id'";
                $result = mysqli_query($conn, $sql);
                if ($result) {
                    $output['status'] = 200;
                    $output['msg'] = "Grid Fault Added Successfully";
                } else {
                    $output['status'] = 400;
                    $output['msg'] = "Grid Fault Not Added";
                }
            } else {
                $output['status'] = 400;
                $output['msg'] = "Grid Fault Not Added";
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
