<?php

include("../../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$output = array();
date_default_timezone_set('Asia/Calcutta');
$today = date('Y-m-d');
$data = json_decode(file_get_contents("php://input"));
$most_recent_month = null;

if (isset($data->user_id)) {
    $user_id = $data->user_id;

    // Check if user_id is in the customer_group table
    $sql_check_group = "SELECT * FROM customer_group WHERE BINARY customergroup_uniq_id = ? AND delete_at = '0'";
    $stmt_check_group = $conn->prepare($sql_check_group);
    $stmt_check_group->bind_param("s", $user_id);
    $stmt_check_group->execute();
    $result_check_group = $stmt_check_group->get_result();

    if ($result_check_group->num_rows > 0) {
        $group_condition = "BINARY cg.customergroup_uniq_id = ?";
    } else {
        // Check if the user_id exists in the customer table
        $sql_check_customer = "SELECT * FROM customer WHERE BINARY customer_unique_id = ? AND delete_at = '0'";
        $stmt_check_customer = $conn->prepare($sql_check_customer);
        $stmt_check_customer->bind_param("s", $user_id);
        $stmt_check_customer->execute();
        $result_check_customer = $stmt_check_customer->get_result();

        if ($result_check_customer->num_rows > 0) {
            $group_condition = "BINARY c.customer_unique_id = ?";
        } else {
            $output["status"] = 400;
            $output["msg"] = "No Data";
            $output["data"] = [];
            echo json_encode($output, JSON_NUMERIC_CHECK);
            exit;
        }
    }

    // Construct query to get monthly aggregated data
    $sql_recent = "SELECT c.*, cg.*, t.wtg_no, t.loc_no, 
                   SUM(dg.total_production) AS total_production,
                   DATE_FORMAT(dg.dg_date, '%Y-%m') AS month,
                   SUM(dg.gridfault_overtotal) AS gridfault_overtotal,
                   SUM(dg.griddrop_overtotal) AS griddrop_overtotal,
                   SUM(dg.error_overtotal) AS error_overtotal
                   FROM customer c 
                   INNER JOIN customer_group cg ON BINARY c.customergroupname_id = BINARY cg.customergroup_uniq_id 
                   LEFT JOIN turbine t ON t.customer_id = c.customer_unique_id
                   LEFT JOIN daily_generation dg ON t.turbine_id = dg.turbine_id
                   WHERE $group_condition AND c.delete_at = '0' AND t.delete_at = '0'
                   AND dg.dg_date <= ?
                   GROUP BY month, t.wtg_no
                   ORDER BY month DESC";

    $stmt_recent = $conn->prepare($sql_recent);
    if ($stmt_recent) {
        // Bind parameters
        $stmt_recent->bind_param("ss", $user_id, $today);  // Bind user_id and date
        $stmt_recent->execute();
        $result_recent = $stmt_recent->get_result();

        if ($result_recent->num_rows > 0) {
            // If recent data exists
            $output["status"] = 200;
            $output["msg"] = "success";
            $output["today_date"] = $today;
            $output["data"] = array();
            $seen_wtg_no = array();  // Array to track seen wtg_no values

            // Determine the most recent month from the result set
            while ($row = $result_recent->fetch_assoc()) {
                $month = isset($row['month']) ? $row['month'] : null;
                if (is_null($most_recent_month) || $month > $most_recent_month) {
                    $most_recent_month = $month;
                }
            }
            if ($most_recent_month) {
                $dateTime = DateTime::createFromFormat('Y-m', $most_recent_month);
                $output["current_month"] = $dateTime->format('F Y'); // This will give you "October 2024"
            } else {
                $output["current_month"] = null; // In case there's no month found
            }
            // Reset pointer to fetch data again
            $result_recent->data_seek(0);

            while ($row = $result_recent->fetch_assoc()) {
                $wtg_no = $row['wtg_no'];
                $month = isset($row['month']) ? $row['month'] : null;

                // Skip row if wtg_no has already been processed
                if (in_array($wtg_no, $seen_wtg_no)) {
                    continue;
                }

                $total_production = ($month === $most_recent_month) ? floatval($row['total_production']) : 0;

                // Define availability values only for the most recent month
                if ($month === $most_recent_month) {
                    $total_hours_decimal = 24.00 * 30; // Assuming 30 days in a month
                    $gridfault_overtotal_decimal = floatval(isset($row['gridfault_overtotal']) ? $row['gridfault_overtotal'] : 0);
                    $griddrop_overtotal_decimal = floatval(isset($row['griddrop_overtotal']) ? $row['griddrop_overtotal'] : 0);
                    $error_overtotal_decimal = floatval(isset($row['error_overtotal']) ? $row['error_overtotal'] : 0);

                    $machine_availability = ($total_hours_decimal != 0)
                        ? number_format((($total_hours_decimal - ($gridfault_overtotal_decimal + $griddrop_overtotal_decimal + $error_overtotal_decimal)) / $total_hours_decimal) * 100, 2)
                        : '0.00';

                    $grid_availability = ($total_hours_decimal != 0)
                        ? number_format((($total_hours_decimal - ($gridfault_overtotal_decimal + $griddrop_overtotal_decimal)) / $total_hours_decimal) * 100, 2)
                        : '0.00';
                } else {
                    // Set values to 0 if the month does not match the most recent month
                    $machine_availability = '0.00';
                    $grid_availability = '0.00';
                }

                $row['total_production'] = $total_production;
                $row['machine_availability'] = $machine_availability;
                $row['grid_availability'] = $grid_availability;

                $output["data"][] = $row;

                // Mark this wtg_no as processed
                $seen_wtg_no[] = $wtg_no;
            }

        } else {
            $output["status"] = 400;
            $output["msg"] = "No Data";
            $output["data"] = [];
        }
    } else {
        $output["status"] = 500;
        $output["msg"] = "Error in preparing statement";
        $output["data"] = [];
    }

} else {
    $output["status"] = 400;
    $output["msg"] = "user_id is required";
}

echo json_encode($output, JSON_NUMERIC_CHECK);

?>