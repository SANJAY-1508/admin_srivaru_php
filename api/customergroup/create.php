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


if (isset($obj->customergroup_name) && isset($obj->customergroup_id) && isset($obj->customeruser_name) && isset($obj->customer_password)) {
    $customergroup_name = $obj->customergroup_name;
    $customergroup_id = $obj->customergroup_id;
    $customeruser_name = $obj->customeruser_name;
    $customer_password = $obj->customer_password;

    if (!empty($customergroup_name)) {
        $checkexitsusersql = "SELECT * FROM `customer_group` WHERE `customergroup_name` = '$customergroup_name'";
        $checkexitsuser = mysqli_query($conn, $checkexitsusersql);
        $checkexitsusercount = mysqli_num_rows($checkexitsuser);
        if ($checkexitsusercount > 0) {
            $output['status'] = 400;
            $output['msg'] = "Customer Group details already exists";
        } else {
            $sql = "INSERT INTO `customer_group`(`customergroup_name`,`customergroup_id`,`customeruser_name`,`customer_password`, `delete_at`, `created_date`)
             VALUES ('$customergroup_name','$customergroup_id','$customeruser_name','$customer_password','0','$timestamp')";
            $result = mysqli_query($conn, $sql);
            if ($result) {
                $id = $conn->insert_id;
                $unquid_id = uniqueID("customer_group", $id);
                $sql = "UPDATE `customer_group` SET `customergroup_uniq_id`='$unquid_id' WHERE `id`='$id'";
                $result = mysqli_query($conn, $sql);
                if ($result) {
                    $output['status'] = 200;
                    $output['msg'] = "Customer Group Added Successfully";
                } else {
                    $output['status'] = 400;
                    $output['msg'] = "Customer Group Not Added";
                }
            } else {
                $output['status'] = 400;
                $output['msg'] = "Customer Group Not Added";
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
