<?php

include ("../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$json = file_get_contents('php://input');
$obj = json_decode($json);
$output = array();

date_default_timezone_set('Asia/Calcutta');
$timestamp = date('Y-m-d');

if (isset($obj->date)) {
    $date = $obj->date;

    // Query to get cumulative verified turbine count up to the specified date
    $verified_turbine_array = [];
    $customer_details = [];

    $turbineResult = $conn->query("SELECT turbine_id, wtg_no, customername_id FROM turbine WHERE delete_at='0' AND dgr_need='YES' AND `date` <= '$date'");
    if ($turbineResult->num_rows > 0) {
        while ($turbineRows = $turbineResult->fetch_assoc()) {
            $verified_turbine_array[] = $turbineRows;
        }
    }

    $verifiedcount = count($verified_turbine_array);

    // Query to get update count for the specified date
    $turbine_ids = array_column($verified_turbine_array, 'turbine_id');
    $turbine_ids_str = implode("','", $turbine_ids);
    $sql = "
        SELECT COUNT(*) as update_count 
        FROM daily_generation dg 
        JOIN turbine t ON dg.turbine_id = t.turbine_id 
        WHERE dg.turbine_id IN ('$turbine_ids_str') 
        AND dg.dg_date = '$date'
        AND dg.delete_at = '0'
    ";
    $daily_generation_sql = $conn->query($sql);
    $update_count = 0;
    if ($daily_generation_sql->num_rows > 0) {
        $row = $daily_generation_sql->fetch_assoc();
        $update_count = (int) $row['update_count'];
    }

    // Calculate balance
    $balance = $verifiedcount - $update_count;
    if ($balance < 0) {
        $balance = 0;
    }

    foreach ($verified_turbine_array as $turbine) {
        $turbine_id = $turbine['turbine_id'];
        $sql = "SELECT COUNT(*) as count FROM daily_generation WHERE turbine_id = '$turbine_id' AND dg_date = '$date' AND delete_at = '0'";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $count = (int) $row['count'];

        if ($count == 0) {
            $customername_id = $turbine['customername_id'];

            $sql_customer = "SELECT customer_name FROM customer WHERE  customer_unique_id='$customername_id' AND delete_at = '0'";
            $result_customer = $conn->query($sql_customer);
            if ($result_customer->num_rows > 0) {
                $customer = $result_customer->fetch_assoc();
                $customer_name = $customer['customer_name'];
                $customer_details[] = [
                    "wtg_no" => $turbine["wtg_no"],
                    "customer_name" => $customer_name
                ];
            }
        }
    }

    $output["data"]["verified"] = array(
        "verifiedcount" => $verifiedcount,
        "updatecount" => $update_count,
        "balance" => $balance,
        "customer_details" => $customer_details
    );

    $output["status"] = 200;
    $output["msg"] = "success";
} else {
    $output["status"] = 400;
    $output["msg"] = "Date parameter missing";
}

echo json_encode($output, JSON_NUMERIC_CHECK);