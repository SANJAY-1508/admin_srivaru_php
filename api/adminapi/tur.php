<?php

include("../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$output = array();

$sql1 = "SELECT COUNT(*) as count_customer
         FROM customer 
         WHERE delete_at = '0'";

$result1 = $conn->query($sql1);

if ($result1->num_rows > 0) {
    $row1 = $result1->fetch_assoc();
    $output['count_customer'] = $row1['count_customer'];
} else {
    $output['count_customer'] = 0;
}
// SQL to get the count of `wtg_no` where `dgr_need = 'yes'`
$sql1 = "SELECT COUNT(*) as count_wtg_no
         FROM turbine 
         WHERE dgr_need = 'yes' 
         AND delete_at = '0'";

$result1 = $conn->query($sql1);

if ($result1->num_rows > 0) {
    $row1 = $result1->fetch_assoc();
    $output['count_wtg_no'] = $row1['count_wtg_no'];
} else {
    $output['count_wtg_no'] = 0;
}



$output['status'] = 200;
$output['msg'] = ($result1->num_rows > 0 || $result2->num_rows > 0 || $result3->num_rows > 0) ? 'Success' : 'No Data';

echo json_encode($output, JSON_NUMERIC_CHECK);