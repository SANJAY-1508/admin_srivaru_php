<?php

include("../config/db_config.php");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

$output = array();

// SQL to get the count of customers where `delete_at = '0'`
$sql_customer = "SELECT COUNT(*) as count_customer
                 FROM customer 
                 WHERE delete_at = '0'";
$result_customer = $conn->query($sql_customer);

if ($result_customer && $result_customer->num_rows > 0) {
    $row_customer = $result_customer->fetch_assoc();
    $output['count_customer'] = $row_customer['count_customer'];
} else {
    $output['count_customer'] = 0;
}

// SQL to get the count of `wtg_no` where `delete_at = '0'`
$sql_wtg = "SELECT COUNT(*) as count_wtg_no
            FROM turbine 
            WHERE delete_at = '0'";
$result_wtg = $conn->query($sql_wtg);

if ($result_wtg && $result_wtg->num_rows > 0) {
    $row_wtg = $result_wtg->fetch_assoc();
    $output['count_wtg_no'] = $row_wtg['count_wtg_no'];
} else {
    $output['count_wtg_no'] = 0;
}

// SQL to get the count of `contract_code` grouped by `contract_code`
$sql_contract = "SELECT ct.contract_code, COUNT(*) as count
                 FROM contract_type ct
                 JOIN turbine t ON ct.contract_id = t.contracttype_id
                 WHERE t.delete_at = '0'
                 GROUP BY ct.contract_code";
$result_contract = $conn->query($sql_contract);

if ($result_contract && $result_contract->num_rows > 0) {
    $output['contract_type'] = array();

    while ($row_contract = $result_contract->fetch_assoc()) {
        $output['contract_type'][] = $row_contract;
    }
} else {
    $output['contract_type'] = [];
}

// SQL to get the count of `model_type` grouped by `model_type`
$sql_model = "SELECT m.model_type, COUNT(*) as count
              FROM model m
              JOIN turbine t ON m.model_id = t.model_id
              WHERE t.delete_at = '0'
              GROUP BY m.model_type";
$result_model = $conn->query($sql_model);

if ($result_model && $result_model->num_rows > 0) {
    $output['model_type'] = array();

    while ($row_model = $result_model->fetch_assoc()) {
        $output['model_type'][] = $row_model;
    }
} else {
    $output['model_type'] = [];
}

// SQL to get the count of turbines grouped by `capacity`
$sql_capacity = "SELECT capacity, COUNT(wtg_no) as count
                 FROM turbine
                 WHERE delete_at = '0'
                 GROUP BY capacity";
$result_capacity = $conn->query($sql_capacity);

if ($result_capacity && $result_capacity->num_rows > 0) {
    $output['capacity_count'] = array();

    while ($row_capacity = $result_capacity->fetch_assoc()) {
        $output['capacity_count'][] = $row_capacity;
    }
} else {
    $output['capacity_count'] = [];
}

// Set status and message based on query results
if ($result_customer || $result_wtg || $result_contract || $result_model || $result_capacity) {
    $output['status'] = 200;
    $output['msg'] = 'Success';
} else {
    $output['status'] = 204;
    $output['msg'] = 'No Data';
}

echo json_encode($output);

?>