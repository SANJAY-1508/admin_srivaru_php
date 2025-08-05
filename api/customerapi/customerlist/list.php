<?php

include ("../../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$output = array();
date_default_timezone_set('Asia/Calcutta');
$timestamp = date('Y-m-d');
$data = json_decode(file_get_contents("php://input"));

if (isset($data->user_id)) {
    $user_id = $data->user_id;
    $sql_check_group = "SELECT * FROM customer_group WHERE BINARY customergroup_uniq_id = ?";
    $stmt_check_group = $conn->prepare($sql_check_group);
    $stmt_check_group->bind_param("s", $user_id);
    $stmt_check_group->execute();
    $result_check_group = $stmt_check_group->get_result();

    if ($result_check_group->num_rows > 0) {
        // Fetch data where user_id is in the customer_group table
        $sql = "SELECT c.*, cg.*, t.wtg_no
                FROM customer c 
                INNER JOIN customer_group cg ON BINARY c.customergroupname_id = BINARY cg.customergroup_uniq_id 
                LEFT JOIN turbine t ON t.customer_id = c.customer_unique_id
                WHERE BINARY cg.customergroup_uniq_id = ? AND c.delete_at = '0' AND t.delete_at = '0'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $user_id);
    } else {
        // Check if the user_id exists in the customer table
        $sql_check_customer = "SELECT * FROM customer WHERE BINARY customer_unique_id = ? AND delete_at = '0'";
        $stmt_check_customer = $conn->prepare($sql_check_customer);
        $stmt_check_customer->bind_param("s", $user_id);
        $stmt_check_customer->execute();
        $result_check_customer = $stmt_check_customer->get_result();

        if ($result_check_customer->num_rows > 0) {
            // Fetch customer data directly if user_id is in the customer table
            $sql = "SELECT c.*, t.wtg_no
                    FROM customer c
                    LEFT JOIN turbine t ON t.customer_id = c.customer_unique_id
                    WHERE BINARY c.customer_unique_id = ? AND c.delete_at = '0' AND t.delete_at = '0'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $user_id);
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
        $output["status"] = 200;
        $output["msg"] = "success";
        $output["data"] = array();
        $customer_data = array();
        while ($row = $sqlresult->fetch_assoc()) {
            $row["errorcode"] = isset($row['errorcode']) ? json_decode($row['errorcode'], true) : null;
            $row["errormaintenance"] = isset($row['errormaintenance']) ? json_decode($row['errormaintenance'], true) : null;
            $row["errorgridfault"] = isset($row['errorgridfault']) ? json_decode($row['errorgridfault'], true) : null;
            $row["errorgriddrop"] = isset($row['errorgriddrop']) ? json_decode($row['errorgriddrop'], true) : null;
            $row["contact"] = isset($row['contact']) ? json_decode($row['contact'], true) : null;
            $row["ship_address"] = isset($row['ship_address']) ? json_decode($row['ship_address'], true) : null;

            $customer_name = $row['customer_name'];
            if (!isset($customer_data[$customer_name])) {
                $customer_data[$customer_name] = $row;
                $customer_data[$customer_name]['wtg_no'] = array();
            }

            if (isset($row['wtg_no'])) {
                $customer_data[$customer_name]['wtg_no'][] = $row['wtg_no'];
            }
        }

        $output["data"] = array_values($customer_data);
    } else {
        $output["status"] = 400;
        $output["msg"] = "No Data";
        $output["data"] = [];
    }
} else {
    $output["status"] = 400;
    $output["msg"] = "user_id is required";
}

echo json_encode($output, JSON_NUMERIC_CHECK);