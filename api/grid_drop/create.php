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


if (isset($obj->grid_drop_code) && isset($obj->grid_drop_describtion)) {
    $grid_drop_code = $obj->grid_drop_code;
    $grid_drop_describtion = $obj->grid_drop_describtion;

    if (!empty($grid_drop_code)) {
        $checkexitsusersql = "SELECT * FROM `grid_drop` WHERE `grid_drop_code` = '$grid_drop_code' AND `delete_at`='0'";
        $checkexitsuser = mysqli_query($conn, $checkexitsusersql);
        $checkexitsusercount = mysqli_num_rows($checkexitsuser);
        if ($checkexitsusercount > 0) {
            $output['status'] = 400;
            $output['msg'] = "Grid Drop details already exists";
        } else {
            $sql = "INSERT INTO `grid_drop`(`grid_drop_code`,`grid_drop_describtion`, `delete_at`, `created_date`)
             VALUES ('$grid_drop_code','$grid_drop_describtion','0','$timestamp')";
            $result = mysqli_query($conn, $sql);
            if ($result) {
                $id = $conn->insert_id;
                $unquid_id  = uniqueID("grid_dropt", $id);
                $sql = "UPDATE `grid_drop` SET `grid_drop_id`='$unquid_id' WHERE `id`='$id'";
                $result = mysqli_query($conn, $sql);
                if ($result) {
                    $output['status'] = 200;
                    $output['msg'] = "Grid Drop Added Successfully";
                } else {
                    $output['status'] = 400;
                    $output['msg'] = "Grid Drop Not Added";
                }
            } else {
                $output['status'] = 400;
                $output['msg'] = "Grid Drop Not Added";
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
