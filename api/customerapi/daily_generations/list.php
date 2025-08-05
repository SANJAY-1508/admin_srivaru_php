<?php

include("../../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$output = array();
date_default_timezone_set('Asia/Calcutta');
$data = json_decode(file_get_contents("php://input"));

if (isset($data->user_id)) {
    $user_id = $data->user_id;

    // Check if user_id is in customer table
    $checkCustomerSql = "SELECT customer_unique_id, customergroupname_id 
                         FROM customer 
                         WHERE customer_unique_id = ? AND delete_at = '0'";
    $checkCustomerStmt = $conn->prepare($checkCustomerSql);
    $checkCustomerStmt->bind_param("s", $user_id);
    $checkCustomerStmt->execute();
    $customerResult = $checkCustomerStmt->get_result();

    if ($customerResult->num_rows > 0) {
        // User is a customer
        $sql = "SELECT dg.*, t.*, c.*, cg.*
                FROM customer c
                INNER JOIN turbine t ON c.customer_unique_id = t.customer_id 
                INNER JOIN daily_generation dg ON t.turbine_id = dg.turbine_id
                INNER JOIN customer_group cg ON c.customergroupname_id = BINARY cg.customergroup_uniq_id
                WHERE c.delete_at = '0' AND t.delete_at = '0' AND dg.delete_at = '0'
                AND c.customer_unique_id = ?
                ORDER BY STR_TO_DATE(dg.dg_date, '%Y-%m-%d') DESC  -- Ordering by most recent dates
                LIMIT 30";  // Limiting to 30 records
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $user_id);
    } else {
        // Check if user_id is in customer_group table
        $checkGroupSql = "SELECT customergroup_uniq_id 
                          FROM customer_group 
                          WHERE customergroup_uniq_id = ?";
        $checkGroupStmt = $conn->prepare($checkGroupSql);
        $checkGroupStmt->bind_param("s", $user_id);
        $checkGroupStmt->execute();
        $groupResult = $checkGroupStmt->get_result();

        if ($groupResult->num_rows > 0) {
            // User is a customer group
            $sql = "SELECT dg.*, t.*, c.*, cg.*
                    FROM customer c
                    INNER JOIN turbine t ON c.customer_unique_id = t.customer_id 
                    INNER JOIN daily_generation dg ON t.turbine_id = dg.turbine_id
                    INNER JOIN customer_group cg ON c.customergroupname_id = BINARY cg.customergroup_uniq_id
                    WHERE c.delete_at = '0' AND t.delete_at = '0' AND dg.delete_at = '0'
                    AND cg.customergroup_uniq_id = ?
                    ORDER BY STR_TO_DATE(dg.dg_date, '%Y-%m-%d') DESC  -- Ordering by most recent dates
                    LIMIT 30";  // Limiting to 30 records
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $user_id);
        } else {
            // Neither customer nor customer group
            $output["status"] = 400;
            $output["msg"] = "Invalid user_id";
            $output["data"]["daily_generation"] = [];
            echo json_encode($output, JSON_NUMERIC_CHECK);
            exit;
        }
    }

    $stmt->execute();
    $sqlresult = $stmt->get_result();
    if ($sqlresult === false) {
        $output["status"] = 500;
        $output["msg"] = "Error: " . $conn->error;
        echo json_encode($output);
        exit;
    }

    if ($sqlresult->num_rows > 0) {
        $output["status"] = 200;
        $output["msg"] = "success";
        $output["data"]["daily_generation"] = array();
        while ($row = $sqlresult->fetch_assoc()) {
            $total_hours = "24:00";

            $row["errorcode"] = json_decode($row['errorcode'], true);
            $row["errormaintenance"] = json_decode($row['errormaintenance'], true);
            $row["errorgridfault"] = json_decode($row['errorgridfault'], true);
            $row["errorgriddrop"] = json_decode($row['errorgriddrop'], true);
            $row["contact"] = json_decode($row['contact'], true);
            $row["ship_address"] = json_decode($row['ship_address'], true);

            // Ensure the variables are cast to floats and are valid
            $gen_onehrs = isset($row['gen_onehrs']) ? timeToDecimal($row['gen_onehrs']) : 0;
            $gen_twohrs = isset($row['gen_twohrs']) ? timeToDecimal($row['gen_twohrs']) : 0;
            $maintenance_overtotal = isset($row['maintenance_overtotal']) ? timeToDecimal($row['maintenance_overtotal']) : 0;
            $error_overtotal = isset($row['error_overtotal']) ? timeToDecimal($row['error_overtotal']) : 0;
            $griddrop_overtotal = isset($row['griddrop_overtotal']) ? timeToDecimal($row['griddrop_overtotal']) : 0;
            $gridfault_overtotal = isset($row['gridfault_overtotal']) ? timeToDecimal($row['gridfault_overtotal']) : 0;
            $kwh_exp = isset($row['kwh_exp']) ? floatval($row['kwh_exp']) : 0;
            $kvarh_imp = isset($row['kvarh_imp']) ? floatval($row['kvarh_imp']) : 0;
            $total_hours_decimal = timeToDecimal($total_hours);

            $turbine_ok_hrs_decimal = $total_hours_decimal - ($error_overtotal + $griddrop_overtotal + $gridfault_overtotal);
            $row['turbine_ok_hrs'] = decimalToTime($turbine_ok_hrs_decimal);
            $lull_hrs = ($turbine_ok_hrs_decimal > 0)
                ? ($turbine_ok_hrs_decimal - ($gen_onehrs + $gen_twohrs + ($maintenance_overtotal)))
                : 0;

            // Convert decimal lull hours back to time format
            $row['lull_hrs'] = decimalToTime($lull_hrs);

            // Add new calculations for grid availability and machine availability
            $grid_availability = ($total_hours_decimal != 0)
                ? (($total_hours_decimal - ($gridfault_overtotal + $griddrop_overtotal)) / $total_hours_decimal) * 100
                : 0;

            $machine_availability = ($total_hours_decimal != 0 && ($total_hours_decimal - ($gridfault_overtotal + $griddrop_overtotal)) != 0)
                ? number_format((($total_hours_decimal - ($gridfault_overtotal + $griddrop_overtotal + $error_overtotal)) / 
                    ($total_hours_decimal - ($gridfault_overtotal + $griddrop_overtotal))) * 100, 2)
                : '0.00';

            $row['kvarh_imp_per'] = ($kwh_exp > 0 && $kvarh_imp > 0)
                ? number_format(($kvarh_imp / $kwh_exp) * 100, 2)
                : '0.00';

            $row['grid_availability'] = number_format($grid_availability, 2);
            $row['machine_availability'] = $machine_availability;

            // Add the modified row to the output array
            $output["data"]["daily_generation"][] = $row;
        }
    } else {
        $output["status"] = 400;
        $output["msg"] = "No Data";
        $output["data"]["daily_generation"] = [];
    }

} else {
    $output["status"] = 400;
    $output["msg"] = "user_id is required";
}

function timeToDecimal($time)
{
    // Check if the time format is correct
    if (preg_match('/^\d{2}:\d{2}$/', $time)) {
        list($hours, $minutes) = explode(':', $time);
        return $hours + ($minutes / 60);
    } else {
        // Handle incorrect format
        return 0;
    }
}

function decimalToTime($decimal)
{
    $hours = floor($decimal);
    $minutes = round(($decimal - $hours) * 60);

    return sprintf("%02d:%02d", $hours, $minutes);
}

echo json_encode($output, JSON_NUMERIC_CHECK);
