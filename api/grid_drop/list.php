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

    // $result = $conn->query("SELECT * FROM `grid_drop` WHERE `grid_drop_code` LIKE '%" . $search_text ."%' AND `delete_at`=0 ORDER BY id DESC");
    $result = $conn->query("SELECT * FROM `grid_drop` WHERE (`grid_drop_code` LIKE '%" . $search_text ."%' OR `grid_drop_describtion` LIKE '%" . $search_text ."%') AND `delete_at`=0 ORDER BY id DESC");
    if ($result->num_rows > 0) {
        $count = 0;
        while ($row = $result->fetch_assoc()) {
            $output["status"] = 200;
            $output["msg"] = "Success";
            $output["data"]["grid_drop"][$count] = $row;
            $count++;
        }
    } else {
        $output["status"] = 200;
        $output["msg"] = "Success";
        $output["data"]["grid_drop"] = [];
    }
} else {
    $output["status"] = 400;
    $output["msg"] = "Parameter is Mismatch";
}

echo json_encode($output, JSON_NUMERIC_CHECK);
