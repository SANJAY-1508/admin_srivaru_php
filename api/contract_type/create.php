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


if (isset($obj->contract_name) && isset($obj->contract_code)) {
    $contract_name = $obj->contract_name;
    $contract_code = $obj->contract_code;

    if (!empty($contract_name) && !empty($contract_code)) {
        $checkexitsusersql = "SELECT * FROM `contract_type` WHERE `delete_at`='0' AND `contract_code` = '$contract_code'";
        $checkexitsuser = mysqli_query($conn, $checkexitsusersql);
        $checkexitsusercount = mysqli_num_rows($checkexitsuser);
        if ($checkexitsusercount > 0) {
            $output['status'] = 400;
            $output['msg'] = "Contract details already exists";
        } else {
            $sql = "INSERT INTO `contract_type`(`contract_name`, `contract_code`, `delete_at`, `created_date`)
             VALUES ('$contract_name','$contract_code','0','$timestamp')";
            $result = mysqli_query($conn, $sql);
            if ($result) {
                $id = $conn->insert_id;
                $unquid_id = uniqueID("Contract", $id);
                $sql = "UPDATE `contract_type` SET `contract_id`='$unquid_id' WHERE `id`='$id'";
                $result = mysqli_query($conn, $sql);
                if ($result) {
                    $output['status'] = 200;
                    $output['msg'] = "Contracter Added Successfully";
                    $output['data']["contract_id"] = $unquid_id;
                } else {
                    $output['status'] = 400;
                    $output['msg'] = "Contracter Not Added";
                }
            } else {
                $output['status'] = 400;
                $output['msg'] = "Contracter Not Added";
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
