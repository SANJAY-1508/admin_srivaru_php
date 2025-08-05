<?php

include("../../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$output = array();
date_default_timezone_set('Asia/Calcutta');
$current_year = date('Y');
$next_year = date('Y', strtotime('+1 year'));
$data = json_decode(file_get_contents("php://input"));

if (isset($data->user_id)) {
    $user_id = $data->user_id;
    $year_found = false;

    while (!$year_found && $current_year >= 2000) { // Assuming a reasonable minimum year
        // Check if the user_id exists in the customer_group table
        $sql_check_group = "SELECT * FROM customer_group WHERE BINARY customergroup_uniq_id = ?";
        $stmt_check_group = $conn->prepare($sql_check_group);
        $stmt_check_group->bind_param("s", $user_id);
        $stmt_check_group->execute();
        $result_check_group = $stmt_check_group->get_result();

        if ($result_check_group->num_rows > 0) {
            $sql = "SELECT c.*, cg.*, t.wtg_no, t.loc_no, SUM(dg.total_production) AS total_production
                    FROM customer c 
                    INNER JOIN customer_group cg ON BINARY c.customergroupname_id = BINARY cg.customergroup_uniq_id 
                    LEFT JOIN turbine t ON t.customer_id = c.customer_unique_id
                    LEFT JOIN daily_generation dg ON dg.turbine_id = t.turbine_id AND YEAR(dg.dg_date) = ?
                    WHERE BINARY cg.customergroup_uniq_id = ? AND c.delete_at = '0'
                    GROUP BY t.wtg_no";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $current_year, $user_id);
        } else {
            // Check if the user_id exists in the customer table
            $sql_check_customer = "SELECT * FROM customer WHERE BINARY customer_unique_id = ? AND delete_at = '0'";
            $stmt_check_customer = $conn->prepare($sql_check_customer);
            $stmt_check_customer->bind_param("s", $user_id);
            $stmt_check_customer->execute();
            $result_check_customer = $stmt_check_customer->get_result();

            if ($result_check_customer->num_rows > 0) {
                // Fetch customer data directly if user_id is in the customer table
                $sql = "SELECT c.customer_name, t.wtg_no, t.loc_no, SUM(dg.total_production) AS total_production
                        FROM customer c
                        LEFT JOIN turbine t ON t.customer_id = c.customer_unique_id
                        LEFT JOIN daily_generation dg ON dg.turbine_id = t.turbine_id AND YEAR(dg.dg_date) = ?
                        WHERE BINARY c.customer_unique_id = ? AND c.delete_at = '0'
                        GROUP BY t.wtg_no";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("is", $current_year, $user_id);
            } else {
                $output["status"] = 400;
                $output["msg"] = "No Data";
                $output["data"] = [];
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
            $year_found = true;
            $output["status"] = 200;
            $output["msg"] = "success";
            $output["data"] = array();
            $customer_data = array();
            $total_production_sum = 0; // Initialize the total production sum

            while ($row = $sqlresult->fetch_assoc()) {
                // Set the current year as a range
                $row["current_year"] = $current_year . '-' . $next_year;
                $customer_name = $row['customer_name'];

                // Accumulate the total production sum
                if (isset($row['total_production'])) {
                    $total_production_sum += $row['total_production'];
                }

                if (isset($data->group_by_customer) && $data->group_by_customer) {
                    if (!isset($customer_data[$customer_name])) {
                        $customer_data[$customer_name] = $row;
                        $customer_data[$customer_name]['wtg_no'] = array();
                    }
                    if (isset($row['wtg_no'])) {
                        $customer_data[$customer_name]['wtg_no'][] = $row['wtg_no'];
                    }
                } else {
                    $output["data"][] = $row;
                }
            }

            if (isset($data->group_by_customer) && $data->group_by_customer) {
                $output["data"] = array_values($customer_data);
            }

            // Add the total production sum to the output
            $output["total_production"] = $total_production_sum;
                        $output["current_year"] = $current_year . '-' . $next_year;

        } else {
            // Decrement the year and try again
            $current_year--;
            $next_year--;
        }
    }

    if (!$year_found) {
        $output["status"] = 400;
        $output["msg"] = "No Data";
        $output["data"] = [];
    }
} else {
    $output["status"] = 400;
    $output["msg"] = "user_id is required";
}

echo json_encode($output, JSON_NUMERIC_CHECK);
