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

// Initialize conditions and parameters
$conditions = ["c.`delete_at` = 0"];
$parameters = [];
$param_types = "";

if (isset($obj->search_text) && !empty($obj->search_text)) {
    $search_text = $obj->search_text;
    $conditions[] = "(c.`customer_name` LIKE ? OR c.`city` LIKE ?)";
    $parameters[] = '%' . $search_text . '%';
    $parameters[] = '%' . $search_text . '%';
    $param_types .= 'ss';
}

if (isset($obj->city) && !empty($obj->city)) {
    $city = $obj->city;
    $conditions[] = "c.`city` LIKE ?";
    $parameters[] = '%' . $city . '%';
    $param_types .= 's';
}

// Construct the SQL query with a JOIN
$query = "SELECT c.*, cg.* 
          FROM `customer` c 
          LEFT JOIN `customer_group` cg ON binary c.`customergroupname_id` = cg.`customergroup_uniq_id` 
          WHERE " . implode(' AND ', $conditions);
$query .= " ORDER BY c.`id` DESC"; // Sorting by id in descending order

// Prepare and execute the query
$stmt = $conn->prepare($query);
if ($parameters) {
    // Dynamically bind parameters
    $stmt->bind_param($param_types, ...$parameters);
}
$stmt->execute();
$result = $stmt->get_result();

// Process the results
if ($result->num_rows > 0) {
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        $output["status"] = 200;
        $output["msg"] = "Success";
        $output["data"]["customer"][$count] = $row;
        $output["data"]["customer"][$count]["ship_address"] = json_decode($row['ship_address']);
        $output["data"]["customer"][$count]["contact"] = json_decode($row['contact']);
        $count++;
    }
} else {
    $output["status"] = 200;
    $output["msg"] = "Success";
    $output["data"]["customer"] = [];
}

$stmt->close();
$conn->close();

echo json_encode($output, JSON_NUMERIC_CHECK);