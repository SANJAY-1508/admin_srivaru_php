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
        $sql = "SELECT c.*, cg.*, t.wtg_no 
                FROM customer c 
                INNER JOIN customer_group cg ON BINARY c.customergroupname_id = BINARY cg.customergroup_uniq_id 
                LEFT JOIN turbine t ON BINARY c.customer_unique_id = BINARY t.customer_id
                WHERE BINARY cg.customergroup_uniq_id = ? AND c.delete_at = '0' AND (t.delete_at = '0' OR t.delete_at IS NULL)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $user_id);
    } else {
        $sql_check_customer = "SELECT * FROM customer WHERE BINARY customer_unique_id = ? AND delete_at = '0'";
        $stmt_check_customer = $conn->prepare($sql_check_customer);
        $stmt_check_customer->bind_param("s", $user_id);
        $stmt_check_customer->execute();
        $result_check_customer = $stmt_check_customer->get_result();

        if ($result_check_customer->num_rows > 0) {
            $sql = "SELECT c.*, t.wtg_no
                    FROM customer c
                    LEFT JOIN turbine t ON BINARY c.customer_unique_id = BINARY t.customer_id
                    WHERE BINARY c.customer_unique_id = ? AND c.delete_at = '0' AND (t.delete_at = '0' OR t.delete_at IS NULL)";
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

        while ($row = $sqlresult->fetch_assoc()) {
            $output["data"][] = $row;
        }
    } else {
        $output["status"] = 400;
        $output["msg"] = "No Data";
        $output["data"] = [];
    }
} else {
    $output["status"] = 400;
    $output["msg"] = "user_id is required";
    $output["data"] = [];
}

echo json_encode($output, JSON_NUMERIC_CHECK);
