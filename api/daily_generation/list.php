<?php

include("../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$json = file_get_contents('php://input');
$obj = json_decode($json);
$output = array();

date_default_timezone_set('Asia/Calcutta');
$timestamp = date('Y-m-d');

if (isset($obj->search_text)) {
    $search_text = $obj->search_text;
    $sql = "SELECT d.*, t.wtg_no
            FROM daily_generation d
            INNER JOIN turbine t ON d.turbine_id = t.turbine_id
            WHERE d.delete_at = '0' AND t.delete_at = '0'
            AND (t.wtg_no LIKE '%$search_text%' OR d.dg_date LIKE '%$search_text%')
            ORDER BY d.id DESC";

    $sqlresult = $conn->query($sql);
    if ($sqlresult->num_rows > 0) {
        $output["status"] = 200;
        $output["msg"] = "success";
        $count = 0;
        while ($row = $sqlresult->fetch_assoc()) {
            $output["data"]["daily_generation"][$count] = $row;
            $output["data"]["daily_generation"][$count]["errorcode"] = json_decode($row['errorcode']);
            $output["data"]["daily_generation"][$count]["errormaintenance"] = json_decode($row['errormaintenance']);
            $output["data"]["daily_generation"][$count]["errorgridfault"] = json_decode($row['errorgridfault']);
            $output["data"]["daily_generation"][$count]["errorgriddrop"] = json_decode($row['errorgriddrop']);
            $count++;
        }
    } else {
        $output["status"] = 200;
        $output["msg"] = "No Data";
        $output["data"]["daily_generation"] = [];
    }
} else {
    $output["status"] = 400;
    $output["msg"] = "Parameter is Mismatch";
}

echo json_encode($output, JSON_NUMERIC_CHECK);